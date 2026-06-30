<?php

namespace App\Services;

use App\Models\ChurchEvent;

/**
 * Archive une retraite clôturée : hors compteurs opérationnels, consultable dans l'historique.
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
}
