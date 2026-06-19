<?php

namespace App\Services;

use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\RetreatParticipant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Affectation chambres (internes) et ateliers (équilibrés par sexe et tranche d'âge).
 */
class RetreatPlacementAssignmentService
{
    public function __construct(
        protected RetreatAtelierQuarantineNotifier $quarantineNotifier,
    ) {}

    /**
     * Affecte chambre (si interne) et atelier selon les règles métier.
     * L'inscription n'est jamais bloquée : en cas d'échec atelier → quarantaine.
     *
     * @param RetreatParticipant $participant Participant à placer
     * @return void
     */
    public function assignBalancedPlacements(RetreatParticipant $participant): void
    {
        if ($this->isExternalParticipant($participant)) {
            if ($participant->chambre_id !== null) {
                $participant->update(['chambre_id' => null]);
            }
        } elseif (! $participant->chambre_id) {
            $this->assignChambreAutomatically($participant);
        }

        if (! $participant->atelier_id || $participant->atelier_quarantine) {
            $this->assignAtelierAutomatically($participant);
        }
    }

    /**
     * Affecte automatiquement une chambre au participant (équilibrage capacité / sexe).
     *
     * @param RetreatParticipant $participant Participant
     * @return array{success: bool, message: string}
     */
    public function assignChambreAutomatically(RetreatParticipant $participant): array
    {
        $participant->refresh();

        if ($this->isExternalParticipant($participant)) {
            return [
                'success' => false,
                'message' => 'Les participants externes ne sont pas hébergés sur le site.',
            ];
        }

        if ($participant->chambre_id) {
            return [
                'success' => false,
                'message' => 'Une chambre est déjà affectée à ce participant.',
            ];
        }

        $chambre = $this->chooseChambreFor($participant);

        if (! $chambre) {
            return [
                'success' => false,
                'message' => 'Aucune chambre disponible (capacité pleine ou incompatible avec le sexe du participant).',
            ];
        }

        $participant->update(['chambre_id' => $chambre->id]);

        return [
            'success' => true,
            'message' => sprintf('Chambre « %s » affectée automatiquement.', $chambre->nom),
        ];
    }

    /**
     * Affecte automatiquement un atelier au participant (équilibrage sexe / tranche d'âge).
     *
     * @param RetreatParticipant $participant Participant
     * @return array{success: bool, message: string}
     */
    public function assignAtelierAutomatically(RetreatParticipant $participant): array
    {
        $participant->refresh();

        if ($participant->atelier_id && ! $participant->atelier_quarantine) {
            return [
                'success' => false,
                'message' => 'Un atelier est déjà assigné à ce participant.',
            ];
        }

        $atelier = $this->chooseAtelierFor($participant);

        if (! $atelier) {
            $this->placeInAtelierQuarantine(
                $participant,
                'Aucun atelier disponible pour l\'âge ou la configuration actuelle.'
            );

            return [
                'success' => false,
                'message' => 'Participant placé en quarantaine atelier en attente de réaffectation.',
                'quarantined' => true,
            ];
        }

        $participant->update([
            'atelier_id' => $atelier->id,
            'atelier_quarantine' => false,
            'atelier_quarantine_at' => null,
        ]);

        return [
            'success' => true,
            'message' => sprintf('Atelier n°%s affecté automatiquement.', $atelier->numero),
            'quarantined' => false,
        ];
    }

    /**
     * Met le participant en quarantaine atelier (inscription non bloquée).
     *
     * @param RetreatParticipant $participant Participant
     * @param string|null $reason Motif affiché aux super_admin
     * @return void
     */
    public function placeInAtelierQuarantine(RetreatParticipant $participant, ?string $reason = null): void
    {
        $participant->refresh();

        $wasQuarantined = (bool) $participant->atelier_quarantine;

        $participant->update([
            'atelier_id' => null,
            'atelier_quarantine' => true,
            'atelier_quarantine_at' => now(),
        ]);

        if (! $wasQuarantined) {
            $this->quarantineNotifier->notifySuperAdminsNewQuarantine($participant, $reason);
        }
    }

    /**
     * Tente de réaffecter un participant à un atelier compatible.
     *
     * @param RetreatParticipant $participant Participant
     * @param bool $releaseFromIneligibleAtelier Retirer d'un atelier hors tranche avant réaffectation
     * @return array{success: bool, message: string, quarantined: bool, atelier_numero: int|null}
     */
    public function reassignParticipantAtelier(
        RetreatParticipant $participant,
        bool $releaseFromIneligibleAtelier = true,
    ): array {
        $participant->refresh();

        if ($releaseFromIneligibleAtelier && filled($participant->atelier_id)) {
            $currentAtelier = RetreatAtelier::query()->find($participant->atelier_id);

            if ($currentAtelier && ! $this->isParticipantEligibleForAtelier($participant, $currentAtelier)) {
                $participant->update(['atelier_id' => null]);
                $participant->refresh();
            }
        }

        $atelier = $this->chooseAtelierFor($participant);

        if ($atelier) {
            $participant->update([
                'atelier_id' => $atelier->id,
                'atelier_quarantine' => false,
                'atelier_quarantine_at' => null,
            ]);

            return [
                'success' => true,
                'message' => sprintf('Atelier n°%s affecté.', $atelier->numero),
                'quarantined' => false,
                'atelier_numero' => (int) $atelier->numero,
            ];
        }

        $this->placeInAtelierQuarantine(
            $participant,
            'Réaffectation automatique impossible : aucun atelier compatible.'
        );

        return [
            'success' => false,
            'message' => 'Aucun atelier compatible — participant maintenu en quarantaine.',
            'quarantined' => true,
            'atelier_numero' => null,
        ];
    }

    /**
     * Réaffecte les participants hors tranche d'un atelier donné.
     *
     * @param RetreatAtelier $atelier Atelier source
     * @return array{reassigned: int, quarantined: int, skipped: int}
     */
    public function reassignMismatchedAtelierParticipants(RetreatAtelier $atelier): array
    {
        $stats = [
            'reassigned' => 0,
            'quarantined' => 0,
            'skipped' => 0,
        ];

        $atelier->loadMissing('participants');

        foreach ($atelier->participants as $participant) {
            if ($this->isParticipantEligibleForAtelier($participant, $atelier)) {
                $stats['skipped']++;

                continue;
            }

            $this->placeInAtelierQuarantine(
                $participant,
                sprintf('Retiré de l\'atelier n°%s : tranche d\'âge incompatible.', $atelier->numero)
            );
            $stats['quarantined']++;
        }

        return $stats;
    }

    /**
     * Tente de réaffecter tous les participants en quarantaine atelier.
     *
     * @param RetreatParticipant $participant Participant
     * @return Collection<int, array{atelier: RetreatAtelier, atelier_id: int, score: float}>
     */
    public function rankEligibleAteliersForParticipant(RetreatParticipant $participant): Collection
    {
        $eventId = $participant->event_id;
        $all = $this->loadAtelierPool(null, $eventId);

        if ($all->isEmpty()) {
            return collect();
        }

        if (! $this->shouldEnforceAtelierAgeRange($participant)) {
            $pool = $all;
        } else {
            $pool = $this->eligibleAteliersForAge($all, (int) $participant->age);
        }

        $participantSexe = $this->normalizeSexe($participant->sexe);
        $participantBand = $this->ageBand((int) $participant->age);

        return $pool
            ->map(fn (RetreatAtelier $atelier): array => [
                'atelier' => $atelier,
                'atelier_id' => (int) $atelier->id,
                'score' => $this->atelierImbalanceScore($atelier, $participantSexe, $participantBand),
            ])
            ->sortBy('score')
            ->values();
    }

    /**
     * Affecte un participant à un atelier après validation manuelle par un admin.
     *
     * @param RetreatParticipant $participant Participant
     * @param int $atelierId Identifiant atelier retenu
     * @return array{success: bool, message: string}
     */
    public function assignParticipantToAtelierByAdmin(RetreatParticipant $participant, int $atelierId): array
    {
        $participant->refresh();
        $atelier = RetreatAtelier::query()->find($atelierId);

        if (! $atelier) {
            return [
                'success' => false,
                'message' => 'Atelier introuvable.',
            ];
        }

        if (! $this->isParticipantEligibleForAtelier($participant, $atelier)) {
            return [
                'success' => false,
                'message' => sprintf(
                    'L\'atelier n°%s (%s) ne correspond pas à l\'âge du participant (%s ans).',
                    $atelier->numero,
                    $this->describeAtelierAgeRange($atelier),
                    $participant->age,
                ),
            ];
        }

        $participant->update([
            'atelier_id' => $atelier->id,
            'atelier_quarantine' => false,
            'atelier_quarantine_at' => null,
        ]);

        return [
            'success' => true,
            'message' => sprintf('Participant affecté à l\'atelier n°%s.', $atelier->numero),
        ];
    }

    /**
     * Crée un nouvel atelier puis affecte le participant (action admin).
     *
     * @param RetreatParticipant $participant Participant
     * @param array{numero: int, age_min: int, age_max: int, responsable_user_id: int, description?: string|null} $data Données atelier
     * @return array{success: bool, message: string, atelier_id?: int}
     */
    public function createAtelierAndAssignParticipant(RetreatParticipant $participant, array $data): array
    {
        $numero = (int) $data['numero'];

        if (RetreatAtelier::query()->where('numero', $numero)->exists()) {
            return [
                'success' => false,
                'message' => sprintf('Le numéro d\'atelier %d existe déjà.', $numero),
            ];
        }

        $ageMin = (int) $data['age_min'];
        $ageMax = (int) $data['age_max'];
        $participantAge = (int) $participant->age;

        if ($participantAge < $ageMin || $participantAge > $ageMax) {
            return [
                'success' => false,
                'message' => sprintf(
                    'La tranche %d – %d ans ne couvre pas l\'âge du participant (%d ans).',
                    $ageMin,
                    $ageMax,
                    $participantAge,
                ),
            ];
        }

        $atelier = RetreatAtelier::query()->create([
            'numero' => $numero,
            'age_min' => $ageMin,
            'age_max' => $ageMax,
            'responsable_user_id' => (int) $data['responsable_user_id'],
            'role_on_atelier' => 'responsable',
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);

        $result = $this->assignParticipantToAtelierByAdmin($participant, (int) $atelier->id);

        if (! $result['success']) {
            $atelier->delete();

            return $result;
        }

        return [
            'success' => true,
            'message' => sprintf('Atelier n°%d créé et participant affecté.', $atelier->numero),
            'atelier_id' => (int) $atelier->id,
        ];
    }

    /**
     * Tente de réaffecter tous les participants en quarantaine atelier.
     *
     * @param int|null $eventId Filtrer par événement
     * @return array{reassigned: int, quarantined: int, skipped: int}
     */
    public function reassignAllQuarantinedParticipants(?int $eventId = null): array
    {
        $stats = [
            'reassigned' => 0,
            'quarantined' => 0,
            'skipped' => 0,
        ];

        $query = RetreatParticipant::query()->where('atelier_quarantine', true);

        if ($eventId !== null) {
            $query->where('event_id', $eventId);
        }

        foreach ($query->get() as $participant) {
            $result = $this->reassignParticipantAtelier($participant, true);

            if ($result['success']) {
                $stats['reassigned']++;
            } else {
                $stats['quarantined']++;
            }
        }

        return $stats;
    }

    /**
     * Compte les participants d'un atelier dont l'âge ne correspond pas à la tranche.
     *
     * @param RetreatAtelier $atelier Atelier
     * @return int Nombre de participants hors tranche
     */
    public function countMismatchedParticipantsForAtelier(RetreatAtelier $atelier): int
    {
        $atelier->loadMissing('participants');

        return $atelier->participants
            ->filter(fn (RetreatParticipant $participant): bool => ! $this->isParticipantEligibleForAtelier($participant, $atelier))
            ->count();
    }

    /**
     * @param RetreatParticipant $participant Participant
     * @return bool Vrai si le participant ne dort pas sur le site (externe)
     */
    public function isExternalParticipant(RetreatParticipant $participant): bool
    {
        $type = strtolower(trim((string) $participant->participant_type));

        if (in_array($type, ['external', 'externe'], true)) {
            return true;
        }

        $hebergement = strtolower(trim((string) $participant->hebergement_choice));

        return in_array($hebergement, ['externe', 'external'], true)
            || str_starts_with($hebergement, 'ext');
    }

    /**
     * @param RetreatParticipant $participant Participant
     * @return bool Vrai si une chambre peut être assignée
     */
    public function requiresChambrePlacement(RetreatParticipant $participant): bool
    {
        return ! $this->isExternalParticipant($participant);
    }

    /**
     * Déduit le type participant à partir du choix d'hébergement à l'inscription.
     *
     * @param string|null $hebergement interne|externe
     * @return string internal|external
     */
    public function participantTypeFromHebergement(?string $hebergement): string
    {
        $value = strtolower(trim((string) $hebergement));

        if (in_array($value, ['externe', 'external'], true) || str_starts_with($value, 'ext')) {
            return 'external';
        }

        return 'internal';
    }

    /**
     * @param Builder<RetreatParticipant> $query Requête participants
     * @return Builder<RetreatParticipant>
     */
    public function scopeEligibleForChambreAssignment(Builder $query): Builder
    {
        return $query
            ->whereNotIn('participant_type', ['external', 'externe'])
            ->where(function (Builder $inner): void {
                $inner->whereNull('hebergement_choice')
                    ->orWhere('hebergement_choice', 'interne')
                    ->orWhere('hebergement_choice', 'like', 'intern%');
            });
    }

    /**
     * @param RetreatParticipant $participant Participant
     * @return RetreatChambre|null Chambre la moins remplie compatible
     */
    public function chooseChambreFor(RetreatParticipant $participant): ?RetreatChambre
    {
        if ($this->isExternalParticipant($participant)) {
            return null;
        }

        $sexe = $this->normalizeSexe($participant->sexe);

        return RetreatChambre::query()
            ->where('is_active', true)
            ->withCount('participants')
            ->where(function ($query) use ($sexe): void {
                $query->whereNull('sexe')
                    ->orWhere('sexe', '')
                    ->orWhere('sexe', 'mixte')
                    ->orWhere('sexe', $sexe);
            })
            ->get()
            ->filter(fn (RetreatChambre $chambre): bool => ($chambre->participants_count ?? 0) < (int) $chambre->capacite)
            ->sortBy([
                fn (RetreatChambre $a, RetreatChambre $b): int => ($a->participants_count ?? 0) <=> ($b->participants_count ?? 0),
                fn (RetreatChambre $a, RetreatChambre $b): int => strnatcasecmp((string) $a->nom, (string) $b->nom),
            ])
            ->first();
    }

    /**
     * Choisit l'atelier le plus équilibré (effectif, mixité H/F, répartition des tranches d'âge).
     *
     * @param RetreatParticipant $participant Participant
     * @return RetreatAtelier|null Atelier retenu
     */
    public function chooseAtelierFor(RetreatParticipant $participant): ?RetreatAtelier
    {
        $eventId = $participant->event_id;
        $all = $this->loadAtelierPool(null, $eventId);

        if ($all->isEmpty()) {
            return null;
        }

        if (! $this->shouldEnforceAtelierAgeRange($participant)) {
            return $this->chooseBalancedAtelierFromPool($all, $participant);
        }

        $pool = $this->eligibleAteliersForAge($all, (int) $participant->age);

        if ($pool->isEmpty()) {
            return null;
        }

        return $this->chooseBalancedAtelierFromPool($pool, $participant);
    }

    /**
     * Indique si le participant peut être affecté à l'atelier (tranche d'âge ou numéros legacy).
     *
     * @param RetreatParticipant $participant Participant
     * @param RetreatAtelier $atelier Atelier cible
     * @return bool Vrai si l'affectation est autorisée
     */
    public function isParticipantEligibleForAtelier(RetreatParticipant $participant, RetreatAtelier $atelier): bool
    {
        if (! $this->shouldEnforceAtelierAgeRange($participant)) {
            return true;
        }

        return $this->atelierMatchesParticipantAge($atelier, (int) $participant->age);
    }

    /**
     * Libellé lisible de la tranche d'âge configurée sur l'atelier.
     *
     * @param RetreatAtelier $atelier Atelier
     * @return string Ex. « 16 – 19 ans » ou « non définie »
     */
    public function describeAtelierAgeRange(RetreatAtelier $atelier): string
    {
        if ($this->hasAgeRangeDefined($atelier)) {
            $min = filled($atelier->age_min) ? (int) $atelier->age_min : null;
            $max = filled($atelier->age_max) ? (int) $atelier->age_max : null;

            return match (true) {
                $min !== null && $max !== null => sprintf('%d – %d ans', $min, $max),
                $min !== null => sprintf('à partir de %d ans', $min),
                $max !== null => sprintf('jusqu\'à %d ans', $max),
                default => 'non définie',
            };
        }

        return 'selon numéro d\'atelier (modèle legacy)';
    }

    /**
     * Les rôles encadrement (ouvrier, responsable…) ne sont pas filtrés par tranche d'âge.
     *
     * @param RetreatParticipant $participant Participant
     * @return bool Vrai si la tranche d'âge doit être appliquée
     */
    public function shouldEnforceAtelierAgeRange(RetreatParticipant $participant): bool
    {
        $role = Str::lower(trim((string) $participant->role_participant));

        foreach (['ouvrier', 'worker', 'encadreur', 'staff', 'responsable', 'volontaire'] as $exemptRole) {
            if (str_contains($role, $exemptRole)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Modèle actuel: 15-19 => 1-5, 20-24 => 6-16, 25-29 => 17-24, 30+ => 25-27.
     *
     * @param int $age Âge du participant
     * @return array<int, int> Numéros d'ateliers éligibles
     */
    public function atelierNumbersForAge(int $age): array
    {
        return match (true) {
            $age >= 30 => range(25, 27),
            $age >= 25 => range(17, 24),
            $age >= 20 => range(6, 16),
            default => range(1, 5),
        };
    }

    /**
     * @param int $age Âge en années
     * @return string Clé de tranche d'âge
     */
    public function ageBand(int $age): string
    {
        return match (true) {
            $age >= 30 => '30+',
            $age >= 25 => '25-29',
            $age >= 20 => '20-24',
            default => '15-19',
        };
    }

    /**
     * @param string|null $sexe Sexe brut
     * @return string homme|femme|autre
     */
    public function normalizeSexe(?string $sexe): string
    {
        $value = strtolower(trim((string) $sexe));

        return match ($value) {
            'm', 'male', 'masculin', 'homme', 'h' => 'homme',
            'f', 'female', 'feminin', 'féminin', 'femme' => 'femme',
            default => $value !== '' ? $value : 'autre',
        };
    }

    /**
     * @param RetreatAtelier $atelier Atelier
     * @return bool Vrai si une tranche d'âge est configurée
     */
    public function hasAgeRangeDefined(RetreatAtelier $atelier): bool
    {
        return filled($atelier->age_min) || filled($atelier->age_max);
    }

    /**
     * @param RetreatAtelier $atelier Atelier
     * @param int $age Âge du participant
     * @return bool Vrai si l'âge entre dans la tranche de l'atelier
     */
    public function matchesAtelierAgeRange(RetreatAtelier $atelier, int $age): bool
    {
        if (filled($atelier->age_min) && $age < (int) $atelier->age_min) {
            return false;
        }

        if (filled($atelier->age_max) && $age > (int) $atelier->age_max) {
            return false;
        }

        return true;
    }

    /**
     * @param RetreatAtelier $atelier Atelier
     * @param int $age Âge du participant
     * @return bool Vrai si l'atelier accepte cet âge
     */
    public function atelierMatchesParticipantAge(RetreatAtelier $atelier, int $age): bool
    {
        if ($this->hasAgeRangeDefined($atelier)) {
            return $this->matchesAtelierAgeRange($atelier, $age);
        }

        return in_array((int) $atelier->numero, $this->atelierNumbersForAge($age), true);
    }

    /**
     * @param Collection<int, RetreatAtelier> $all Ateliers actifs
     * @param int $age Âge du participant
     * @return Collection<int, RetreatAtelier> Ateliers éligibles (peut être vide)
     */
    private function eligibleAteliersForAge(Collection $all, int $age): Collection
    {
        return $all->filter(fn (RetreatAtelier $atelier): bool => $this->atelierMatchesParticipantAge($atelier, $age));
    }

    /**
     * @param Collection<int, RetreatAtelier> $pool Ateliers candidats
     * @param RetreatParticipant $participant Participant à placer
     * @return RetreatAtelier|null Atelier le plus équilibré
     */
    private function chooseBalancedAtelierFromPool(Collection $pool, RetreatParticipant $participant): ?RetreatAtelier
    {
        if ($pool->isEmpty()) {
            return null;
        }

        $participantSexe = $this->normalizeSexe($participant->sexe);
        $participantBand = $this->ageBand((int) $participant->age);

        return $pool
            ->sortBy(fn (RetreatAtelier $atelier): float => $this->atelierImbalanceScore(
                $atelier,
                $participantSexe,
                $participantBand
            ))
            ->first();
    }

    /**
     * @param array<int, int>|null $preferredNumbers Numéros d'ateliers ciblés par l'âge
     * @param int|null $eventId Événement (filtre optionnel)
     * @return Collection<int, RetreatAtelier>
     */
    private function loadAtelierPool(?array $preferredNumbers, ?int $eventId): Collection
    {
        $query = RetreatAtelier::query()
            ->where('is_active', true)
            ->withCount(['participants' => function ($relation) use ($eventId): void {
                if ($eventId !== null) {
                    $relation->where('event_id', $eventId);
                }
            }])
            ->with(['participants' => function ($relation) use ($eventId): void {
                $relation->select('id', 'atelier_id', 'sexe', 'age', 'event_id');
                if ($eventId !== null) {
                    $relation->where('event_id', $eventId);
                }
            }])
            ->orderBy('numero');

        if ($preferredNumbers !== null && $preferredNumbers !== []) {
            $query->whereIn('numero', $preferredNumbers);
        }

        return $query->get();
    }

    /**
     * Score d'équilibre (plus bas = meilleur choix) après ajout simulé du participant.
     *
     * @param RetreatAtelier $atelier Atelier candidat
     * @param string $participantSexe Sexe normalisé
     * @param string $participantBand Tranche d'âge
     * @return float Score
     */
    private function atelierImbalanceScore(
        RetreatAtelier $atelier,
        string $participantSexe,
        string $participantBand
    ): float {
        $sexCounts = ['homme' => 0, 'femme' => 0, 'autre' => 0];
        $bandCounts = [
            '15-19' => 0,
            '20-24' => 0,
            '25-29' => 0,
            '30+' => 0,
        ];

        foreach ($atelier->participants as $existing) {
            $sex = $this->normalizeSexe($existing->sexe);
            $sexCounts[$sex] = ($sexCounts[$sex] ?? 0) + 1;
            $band = $this->ageBand((int) $existing->age);
            $bandCounts[$band] = ($bandCounts[$band] ?? 0) + 1;
        }

        $sexCounts[$participantSexe] = ($sexCounts[$participantSexe] ?? 0) + 1;
        $bandCounts[$participantBand] = ($bandCounts[$participantBand] ?? 0) + 1;

        $total = array_sum($sexCounts);
        $hommes = $sexCounts['homme'] ?? 0;
        $femmes = $sexCounts['femme'] ?? 0;
        $sexImbalance = abs($hommes - $femmes);

        $bandValues = array_values($bandCounts);
        $ageSpread = max($bandValues) - min($bandValues);

        return ($total * 100) + ($sexImbalance * 40) + ($ageSpread * 25);
    }
}
