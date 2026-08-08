<?php

namespace App\Services\Sms;

use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Support\RetreatMailUrl;

/**
 * Remplace les variables {{…}} d’un modèle SMS et expose le catalogue documenté.
 */
class SmsTemplateRenderer
{
    public function __construct(
        protected SmsSegmentCounter $segmentCounter,
    ) {}

    /**
     * Catalogue des variables disponibles (clé => libellé).
     *
     * @return array<string, string>
     */
    public function availableVariables(): array
    {
        return [
            'prenom' => 'Prénom',
            'nom' => 'Nom',
            'postnom' => 'Postnom',
            'nom_complet' => 'Nom complet',
            'telephone' => 'Téléphone',
            'email' => 'E-mail',
            'atelier' => 'Atelier',
            'chambre' => 'Chambre',
            'evenement' => 'Événement',
            'lien_billet' => 'Lien billet (paiement validé)',
            'lien_justificatif' => 'Lien justificatif',
            'lien_acces' => 'Lien accès',
            'lien_inscription' => 'Lien portail inscription (tous destinataires)',
        ];
    }

    /**
     * Rend le corps SMS pour un participant (ou numéro manuel sans fiche).
     *
     * @param  string  $body  Corps avec {{variables}}
     * @param  RetreatParticipant|null  $participant  Participant optionnel
     * @param  array<string, string>  $extra  Valeurs supplémentaires / surcharges
     * @return string Texte final
     */
    public function render(string $body, ?RetreatParticipant $participant = null, array $extra = []): string
    {
        $values = array_merge($this->resolveParticipantValues($participant), $extra);

        return (string) preg_replace_callback(
            '/\{\{\s*([a-z0-9_]+)\s*\}\}/i',
            function (array $matches) use ($values): string {
                $key = strtolower($matches[1]);

                return (string) ($values[$key] ?? '');
            },
            $body
        );
    }

    /**
     * Rend + analyse segments pour l’aperçu UI.
     *
     * @param  string  $body  Corps modèle
     * @param  RetreatParticipant|null  $participant  Participant d’aperçu
     * @param  array<string, string>  $extra  Surcharges
     * @return array{
     *     text: string,
     *     encoding: string,
     *     character_count: int,
     *     segments: int,
     *     billet_unavailable: bool,
     *     warnings: list<string>
     * }
     */
    public function preview(string $body, ?RetreatParticipant $participant = null, array $extra = []): array
    {
        $values = array_merge($this->resolveParticipantValues($participant), $extra);
        $text = $this->render($body, $participant, $extra);
        $analysis = $this->segmentCounter->analyze($text);
        $warnings = [];
        $billetUnavailable = false;
        $usesBillet = (bool) preg_match('/\{\{\s*lien_billet\s*\}\}/i', $body);

        if ($usesBillet && ($values['lien_billet'] ?? '') === '') {
            $billetUnavailable = true;
            $warnings[] = 'Billet indisponible (paiement non validé ou token manquant).';
        }

        if ($participant === null && $extra === []) {
            $warnings[] = 'Aperçu sans participant : variables personnelles vides.';
        }

        if ($analysis['segments'] > 1) {
            $warnings[] = 'Message multi-segments ('.$analysis['segments'].' SMS).';
        }

        return [
            'text' => $text,
            'encoding' => $analysis['encoding'],
            'character_count' => $analysis['character_count'],
            'segments' => $analysis['segments'],
            'billet_unavailable' => $billetUnavailable,
            'warnings' => $warnings,
        ];
    }

    /**
     * Valeurs résolues depuis un participant (chaînes vides si absent).
     *
     * @param  RetreatParticipant|null  $participant  Participant
     * @return array<string, string>
     */
    public function resolveParticipantValues(?RetreatParticipant $participant): array
    {
        $lienInscription = RetreatMailUrl::shortInscription();

        if ($participant === null) {
            return array_merge($this->emptyValues(), [
                'lien_inscription' => $lienInscription,
                'evenement' => $this->defaultEventName(),
            ]);
        }

        $participant->loadMissing(['atelier', 'chambre', 'event']);

        $prenom = trim((string) ($participant->prenom ?? ''));
        $nom = trim((string) ($participant->nom ?? ''));
        $postnom = trim((string) ($participant->postnom ?? ''));
        $token = trim((string) ($participant->download_token ?? ''));

        // Sans « ° » ni accents forcés : reste en GSM-7 (160 car./SMS) autant que possible.
        $atelier = $participant->atelier
            ? 'Atelier n'.$participant->atelier->numero
            : '';
        $chambre = $participant->chambre
            ? (string) ($participant->chambre->nom ?? '')
            : '';
        $evenement = $participant->event
            ? (string) ($participant->event->name ?? '')
            : $this->defaultEventName();

        // Liens courts /b|/a|/j — les URL longues se coupent en SMS multi-segments.
        $lienBillet = '';
        if ($participant->paiement_valide && $token !== '') {
            $lienBillet = RetreatMailUrl::shortBillet($token);
        }

        $lienJustificatif = $token !== ''
            ? RetreatMailUrl::shortJustificatif($token)
            : '';
        $lienAcces = $token !== ''
            ? RetreatMailUrl::shortAcces($token)
            : '';

        return [
            'prenom' => $prenom,
            'nom' => $nom,
            'postnom' => $postnom,
            'nom_complet' => trim($prenom.' '.$nom.' '.$postnom),
            'telephone' => trim((string) ($participant->telephone ?? '')),
            'email' => trim((string) ($participant->email ?? '')),
            'atelier' => $atelier,
            'chambre' => $chambre,
            'evenement' => $evenement,
            'lien_billet' => $lienBillet,
            'lien_justificatif' => $lienJustificatif,
            'lien_acces' => $lienAcces,
            'lien_inscription' => $lienInscription,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function emptyValues(): array
    {
        $empty = [];
        foreach (array_keys($this->availableVariables()) as $key) {
            $empty[$key] = '';
        }

        return $empty;
    }

    /**
     * Nom de l’événement opérationnel courant (utile pour numéros manuels).
     */
    protected function defaultEventName(): string
    {
        $event = ChurchEvent::query()
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->orderByDesc('id')
            ->first();

        return $event ? (string) $event->name : 'Grande Retraite des Jeunes';
    }
}
