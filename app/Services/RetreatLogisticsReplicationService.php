<?php

namespace App\Services;

use App\Models\ChurchEvent;
use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\RetreatParticipant;
use Illuminate\Support\Facades\DB;

/**
 * Reconduit ateliers et chambres d'une retraite source vers une nouvelle édition.
 * Les fiches existantes (même numéro + responsable) sont réutilisées, pas dupliquées.
 */
class RetreatLogisticsReplicationService
{
    /**
     * Rend disponibles les ateliers et chambres utilisés lors d'une retraite source.
     *
     * @param  ChurchEvent  $source Retraite de référence
     * @param  ChurchEvent  $target Nouvelle retraite (non archivée)
     * @return array{
     *   ateliers_created: int,
     *   ateliers_reused: int,
     *   chambres_created: int,
     *   chambres_reused: int
     * }
     */
    public function replicateFromEvent(ChurchEvent $source, ChurchEvent $target): array
    {
        if ($target->isArchived()) {
            throw new \InvalidArgumentException('Impossible de reconduire vers un événement archivé.');
        }

        $atelierIds = RetreatParticipant::query()
            ->where('event_id', $source->id)
            ->whereNotNull('atelier_id')
            ->distinct()
            ->pluck('atelier_id');

        $chambreIds = RetreatParticipant::query()
            ->where('event_id', $source->id)
            ->whereNotNull('chambre_id')
            ->distinct()
            ->pluck('chambre_id');

        $stats = [
            'ateliers_created' => 0,
            'ateliers_reused' => 0,
            'chambres_created' => 0,
            'chambres_reused' => 0,
        ];

        DB::transaction(function () use ($atelierIds, $chambreIds, &$stats): void {
            foreach (RetreatAtelier::query()->whereIn('id', $atelierIds)->get() as $atelier) {
                $result = $this->ensureAtelier($atelier);
                $stats[$result === 'created' ? 'ateliers_created' : 'ateliers_reused']++;
            }

            foreach (RetreatChambre::query()->whereIn('id', $chambreIds)->get() as $chambre) {
                $result = $this->ensureChambre($chambre);
                $stats[$result === 'created' ? 'chambres_created' : 'chambres_reused']++;
            }
        });

        return $stats;
    }

    /**
     * Crée l'atelier s'il n'existe pas, sinon réactive la fiche existante.
     *
     * @param  RetreatAtelier  $template Modèle issu de la retraite source
     * @return string created|reused
     */
    private function ensureAtelier(RetreatAtelier $template): string
    {
        $existing = RetreatAtelier::query()
            ->where('numero', $template->numero)
            ->where('responsable_user_id', $template->responsable_user_id)
            ->first();

        if ($existing) {
            if (! $existing->is_active) {
                $existing->update(['is_active' => true]);
            }

            return 'reused';
        }

        RetreatAtelier::query()->create([
            'numero' => $template->numero,
            'age_min' => $template->age_min,
            'age_max' => $template->age_max,
            'responsable_user_id' => $template->responsable_user_id,
            'adjoint_user_id' => $template->adjoint_user_id,
            'role_on_atelier' => $template->role_on_atelier,
            'description' => $template->description,
            'is_active' => true,
        ]);

        return 'created';
    }

    /**
     * Crée la chambre si elle n'existe pas, sinon réactive la fiche existante.
     *
     * @param  RetreatChambre  $template Modèle issu de la retraite source
     * @return string created|reused
     */
    private function ensureChambre(RetreatChambre $template): string
    {
        $existing = RetreatChambre::query()
            ->where('nom', $template->nom)
            ->where('sexe', $template->sexe)
            ->where('responsable_user_id', $template->responsable_user_id)
            ->first();

        if ($existing) {
            if (! $existing->is_active) {
                $existing->update(['is_active' => true]);
            }

            return 'reused';
        }

        RetreatChambre::query()->create([
            'nom' => $template->nom,
            'capacite' => $template->capacite,
            'sexe' => $template->sexe,
            'responsable_user_id' => $template->responsable_user_id,
            'role_on_chambre' => $template->role_on_chambre,
            'description' => $template->description,
            'is_active' => true,
        ]);

        return 'created';
    }

    /**
     * Message utilisateur après reconduction.
     *
     * @param  array<string, int>  $stats Statistiques de reconduction
     * @return string
     */
    public function summaryMessage(array $stats): string
    {
        $parts = [];

        if ($stats['ateliers_created'] > 0) {
            $parts[] = "{$stats['ateliers_created']} atelier(s) créé(s)";
        }
        if ($stats['ateliers_reused'] > 0) {
            $parts[] = "{$stats['ateliers_reused']} atelier(s) déjà disponible(s)";
        }
        if ($stats['chambres_created'] > 0) {
            $parts[] = "{$stats['chambres_created']} chambre(s) créée(s)";
        }
        if ($stats['chambres_reused'] > 0) {
            $parts[] = "{$stats['chambres_reused']} chambre(s) déjà disponible(s)";
        }

        if ($parts === []) {
            return 'Aucun atelier ni chambre trouvé sur la retraite source.';
        }

        return implode(' · ', $parts).'.';
    }

    /**
     * Liste les retraites éligibles comme source (avec participants affectés).
     *
     * @return array<int, string> id => label
     */
    public function sourceEventOptions(?int $excludeEventId = null): array
    {
        return ChurchEvent::query()
            ->when($excludeEventId, fn ($q) => $q->whereKeyNot($excludeEventId))
            ->whereHas('participants')
            ->orderByDesc('start_at')
            ->pluck('name', 'id')
            ->all();
    }
}
