<?php

namespace App\Services;

use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\RetreatParticipant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Affectation chambres (internes) et ateliers (équilibrés par sexe et tranche d'âge).
 */
class RetreatPlacementAssignmentService
{
    /**
     * Affecte chambre (si interne) et atelier selon les règles métier.
     *
     * @param RetreatParticipant $participant Participant à placer
     * @return void
     */
    public function assignBalancedPlacements(RetreatParticipant $participant): void
    {
        $updates = [];

        if ($this->isExternalParticipant($participant)) {
            if ($participant->chambre_id !== null) {
                $updates['chambre_id'] = null;
            }
        } elseif (! $participant->chambre_id) {
            $chambre = $this->chooseChambreFor($participant);
            if ($chambre) {
                $updates['chambre_id'] = $chambre->id;
            }
        }

        if (! $participant->atelier_id) {
            $atelier = $this->chooseAtelierFor($participant);
            if ($atelier) {
                $updates['atelier_id'] = $atelier->id;
            }
        }

        if ($updates !== []) {
            $participant->update($updates);
        }
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
        $age = (int) $participant->age;
        $eventId = $participant->event_id;

        $all = $this->loadAtelierPool(null, $eventId);
        if ($all->isEmpty()) {
            return null;
        }

        $eligible = $all->filter(fn (RetreatAtelier $atelier): bool => $this->matchesAtelierAgeRange($atelier, $age));

        $withExplicitRange = $eligible->filter(fn (RetreatAtelier $atelier): bool => $this->hasAgeRangeDefined($atelier));

        if ($withExplicitRange->isNotEmpty()) {
            $pool = $withExplicitRange;
        } elseif ($eligible->isNotEmpty()) {
            $pool = $eligible;
        } else {
            $preferredNumbers = $this->atelierNumbersForAge($age);
            $pool = $all->filter(fn (RetreatAtelier $atelier): bool => in_array((int) $atelier->numero, $preferredNumbers, true));

            if ($pool->isEmpty()) {
                $pool = $all;
            }
        }

        $participantSexe = $this->normalizeSexe($participant->sexe);
        $participantBand = $this->ageBand($age);

        return $pool
            ->sortBy(fn (RetreatAtelier $atelier): float => $this->atelierImbalanceScore(
                $atelier,
                $participantSexe,
                $participantBand
            ))
            ->first();
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
     * @param array<int, int>|null $preferredNumbers Numéros d'ateliers ciblés par l'âge
     * @param int|null $eventId Événement (filtre optionnel)
     * @return Collection<int, RetreatAtelier>
     */
    private function loadAtelierPool(?array $preferredNumbers, ?int $eventId): Collection
    {
        $query = RetreatAtelier::query()
            ->where('is_active', true)
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
