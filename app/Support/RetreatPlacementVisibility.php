<?php

namespace App\Support;

use App\Models\RetreatParticipant;
use Carbon\CarbonInterface;

/**
 * Règles d'affichage des affectations chambre / atelier côté participant.
 */
final class RetreatPlacementVisibility
{
    private function __construct()
    {
    }

    /**
     * Les placements sont visibles à partir du jour et de l'heure de début de la retraite.
     *
     * @param  RetreatParticipant  $participant Participant inscrit
     * @return bool
     */
    public static function shouldReveal(RetreatParticipant $participant): bool
    {
        $startAt = $participant->event?->start_at;

        if (! $startAt instanceof CarbonInterface) {
            return false;
        }

        return now()->gte($startAt);
    }

    /**
     * Message affiché tant que les placements ne sont pas encore révélés.
     *
     * @param  RetreatParticipant  $participant Participant inscrit
     * @return string
     */
    public static function pendingMessage(RetreatParticipant $participant): string
    {
        $startAt = $participant->event?->start_at;

        if (! $startAt instanceof CarbonInterface) {
            return 'Vos affectations chambre et atelier seront communiquées avant le début de la retraite.';
        }

        return sprintf(
            'Vos affectations chambre et atelier seront visibles à partir du %s.',
            $startAt->timezone(config('app.timezone'))->format('d/m/Y à H:i')
        );
    }
}
