<?php

namespace App\Support;

use App\Models\ChurchEvent;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filtres pour exclure les participants des événements archivés des vues opérationnelles.
 */
class RetreatActiveEventScope
{
    /**
     * Restreint une requête participant aux événements non archivés (ou sans événement).
     *
     * @param  Builder<\App\Models\RetreatParticipant>  $query
     * @return Builder<\App\Models\RetreatParticipant>
     */
    public static function applyToParticipants(Builder $query): Builder
    {
        return $query->where(function (Builder $inner): void {
            $inner->whereNull('event_id')
                ->orWhereHas('event', fn (Builder $eventQuery): Builder => $eventQuery->whereNull('archived_at'));
        });
    }

    /**
     * Compte les participants actifs (hors événements archivés) pour une chambre ou un atelier.
     *
     * @param  Builder<\App\Models\RetreatParticipant>  $query
     * @return Builder<\App\Models\RetreatParticipant>
     */
    public static function applyToParticipantCount(Builder $query): Builder
    {
        return self::applyToParticipants($query);
    }

    /**
     * @param  Builder<ChurchEvent>  $query
     * @return Builder<ChurchEvent>
     */
    public static function onlyOperationalEvents(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<\App\Models\RetreatPayment>  $query
     * @return Builder<\App\Models\RetreatPayment>
     */
    public static function applyToPayments(Builder $query): Builder
    {
        return $query->where(function (Builder $inner): void {
            $inner->whereNull('event_id')
                ->orWhereHas('event', fn (Builder $eventQuery): Builder => $eventQuery->whereNull('archived_at'));
        });
    }
}
