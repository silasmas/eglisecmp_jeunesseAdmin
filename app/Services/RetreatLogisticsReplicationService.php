<?php

namespace App\Services;

use App\Models\ChurchEvent;
use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\RetreatParticipant;
use Illuminate\Support\Facades\DB;

/**
 * Duplique ateliers et chambres utilisés lors d'une retraite pour une nouvelle édition.
 */
class RetreatLogisticsReplicationService
{
    /**
     * Copie les ateliers et chambres liés aux participants d'un événement source.
     *
     * @param  ChurchEvent  $source Retraite de référence
     * @param  ChurchEvent  $target Nouvelle retraite (non archivée)
     * @return array{ateliers: int, chambres: int} Nombre d'enregistrements créés
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

        $ateliersCreated = 0;
        $chambresCreated = 0;

        DB::transaction(function () use ($atelierIds, $chambreIds, &$ateliersCreated, &$chambresCreated): void {
            foreach (RetreatAtelier::query()->whereIn('id', $atelierIds)->get() as $atelier) {
                RetreatAtelier::query()->create([
                    'numero' => $atelier->numero,
                    'age_min' => $atelier->age_min,
                    'age_max' => $atelier->age_max,
                    'responsable_user_id' => $atelier->responsable_user_id,
                    'adjoint_user_id' => $atelier->adjoint_user_id,
                    'role_on_atelier' => $atelier->role_on_atelier,
                    'description' => $atelier->description,
                    'is_active' => true,
                ]);
                $ateliersCreated++;
            }

            foreach (RetreatChambre::query()->whereIn('id', $chambreIds)->get() as $chambre) {
                RetreatChambre::query()->create([
                    'nom' => $chambre->nom,
                    'capacite' => $chambre->capacite,
                    'sexe' => $chambre->sexe,
                    'responsable_user_id' => $chambre->responsable_user_id,
                    'role_on_chambre' => $chambre->role_on_chambre,
                    'description' => $chambre->description,
                    'is_active' => true,
                ]);
                $chambresCreated++;
            }
        });

        return [
            'ateliers' => $ateliersCreated,
            'chambres' => $chambresCreated,
        ];
    }

    /**
     * Liste les retraites éligibles comme source (non archivées, avec participants).
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
