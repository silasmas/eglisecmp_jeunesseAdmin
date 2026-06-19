<?php

namespace App\Support;

use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use Illuminate\Contracts\View\View;

/**
 * Contrôle l'accès public aux contenus d'une retraite clôturée.
 */
class RetreatPublicPortalGate
{
    /**
     * Vrai si l'événement n'est plus accessible côté site public.
     *
     * @param ChurchEvent|null $event Événement retraite
     * @return bool
     */
    public static function isEventPubliclyClosed(?ChurchEvent $event): bool
    {
        if (! $event) {
            return true;
        }

        return (bool) $event->is_publicly_closed;
    }

    /**
     * Vue de clôture pour un événement retraite.
     *
     * @param ChurchEvent $event Événement clôturé
     * @return View
     */
    public static function closedEventView(ChurchEvent $event): View
    {
        return view('retraite-inscription.event-closed', [
            'event' => $event,
        ]);
    }

    /**
     * Vue de clôture pour un participant lié à une retraite fermée.
     *
     * @param RetreatParticipant $participant Participant
     * @return View
     */
    public static function participantEventClosedView(RetreatParticipant $participant): View
    {
        $participant->loadMissing('event');

        if (! $participant->event) {
            abort(404);
        }

        return self::closedEventView($participant->event);
    }
}
