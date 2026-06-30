<?php

namespace App\Services;

use App\Models\ChurchEvent;
use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\RetreatParticipant;

/**
 * Active ou désactive ateliers et chambres liés à une retraite (catalogue global partagé).
 */
class RetreatEventLogisticsLifecycleService
{
    /**
     * Désactive les ateliers et chambres utilisés par les participants de l'événement.
     *
     * @param  ChurchEvent  $event Retraite clôturée ou archivée
     * @return array{ateliers: int, chambres: int} Nombre de fiches désactivées
     */
    public function deactivateForEvent(ChurchEvent $event): array
    {
        $atelierIds = $this->atelierIdsForEvent($event);
        $chambreIds = $this->chambreIdsForEvent($event);

        $ateliers = 0;
        $chambres = 0;

        if ($atelierIds !== []) {
            $ateliers = RetreatAtelier::query()
                ->whereIn('id', $atelierIds)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        if ($chambreIds !== []) {
            $chambres = RetreatChambre::query()
                ->whereIn('id', $chambreIds)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        return [
            'ateliers' => (int) $ateliers,
            'chambres' => (int) $chambres,
        ];
    }

    /**
     * Identifiants d'ateliers affectés aux participants de l'événement.
     *
     * @param  ChurchEvent  $event Événement source
     * @return array<int, int>
     */
    public function atelierIdsForEvent(ChurchEvent $event): array
    {
        return RetreatParticipant::query()
            ->where('event_id', $event->getKey())
            ->whereNotNull('atelier_id')
            ->distinct()
            ->pluck('atelier_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Identifiants de chambres affectées aux participants de l'événement.
     *
     * @param  ChurchEvent  $event Événement source
     * @return array<int, int>
     */
    public function chambreIdsForEvent(ChurchEvent $event): array
    {
        return RetreatParticipant::query()
            ->where('event_id', $event->getKey())
            ->whereNotNull('chambre_id')
            ->distinct()
            ->pluck('chambre_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
