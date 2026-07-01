<?php

namespace App\Services;

use App\Models\ChurchEvent;
use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Archive ou restaure une retraite (visibilité opérationnelle participants / logistique).
 */
class ChurchEventArchiveService
{
    /**
     * Archive l'événement (fermeture publique + désactivation + horodatage).
     *
     * @param  ChurchEvent  $event Événement à archiver
     * @return ChurchEvent Événement rafraîchi
     */
    public function archive(ChurchEvent $event): ChurchEvent
    {
        $event->forceFill([
            'is_active' => false,
            'is_publicly_closed' => true,
            'archived_at' => $event->archived_at ?? now(),
        ])->save();

        return $event->fresh();
    }

    /**
     * Restaure une retraite archivée : réouverture admin, logistique et accès public.
     *
     * @param  ChurchEvent  $event Événement archivé
     * @param  bool  $activate Activer comme événement courant (un seul actif)
     * @return ChurchEvent Événement rafraîchi
     */
    public function restore(ChurchEvent $event, bool $activate = false): ChurchEvent
    {
        if (! $event->isArchived()) {
            throw ValidationException::withMessages([
                'archived_at' => 'Cet événement n\'est pas archivé.',
            ]);
        }

        DB::transaction(function () use ($event, $activate): void {
            if ($activate) {
                ChurchEvent::query()
                    ->where('is_active', true)
                    ->whereKeyNot($event->getKey())
                    ->update(['is_active' => false]);
            }

            $event->forceFill([
                'archived_at' => null,
                'is_publicly_closed' => false,
                'is_active' => $activate ? true : $event->is_active,
            ])->save();

            RetreatAtelier::query()
                ->where('event_id', $event->getKey())
                ->update(['is_active' => true]);

            RetreatChambre::query()
                ->where('event_id', $event->getKey())
                ->update(['is_active' => true]);
        });

        return $event->fresh();
    }
}
