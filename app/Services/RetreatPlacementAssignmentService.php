<?php

namespace App\Services;

use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\RetreatParticipant;

class RetreatPlacementAssignmentService
{
    /**
     * Modèle de base:
     * - chambres: répartition par sexe, équilibrée par occupation/capacité;
     * - ateliers: répartition par tranche d'âge, équilibrée dans la tranche.
     */
    public function assignBalancedPlacements(RetreatParticipant $participant): void
    {
        $updates = [];

        if (! $participant->chambre_id) {
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

    public function chooseChambreFor(RetreatParticipant $participant): ?RetreatChambre
    {
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

    public function chooseAtelierFor(RetreatParticipant $participant): ?RetreatAtelier
    {
        $age = (int) $participant->age;
        $preferredNumbers = $this->atelierNumbersForAge($age);

        $baseQuery = RetreatAtelier::query()
            ->where('is_active', true)
            ->withCount('participants');

        $preferred = (clone $baseQuery)
            ->whereIn('numero', $preferredNumbers)
            ->get();

        $pool = $preferred->isNotEmpty()
            ? $preferred
            : $baseQuery->get();

        return $pool
            ->sortBy([
                fn (RetreatAtelier $a, RetreatAtelier $b): int => ($a->participants_count ?? 0) <=> ($b->participants_count ?? 0),
                fn (RetreatAtelier $a, RetreatAtelier $b): int => (int) $a->numero <=> (int) $b->numero,
            ])
            ->first();
    }

    /**
     * Modèle actuel: 15-19 => 1-5, 20-24 => 6-16, 25-29 => 17-24, 30+ => 25-27.
     * Si les ateliers changent, les numéros absents sont ignorés et un fallback global est appliqué.
     *
     * @return array<int, int>
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

    public function normalizeSexe(?string $sexe): string
    {
        $value = strtolower(trim((string) $sexe));

        return match ($value) {
            'm', 'male', 'masculin', 'homme' => 'homme',
            'f', 'female', 'feminin', 'féminin', 'femme' => 'femme',
            default => $value,
        };
    }
}
