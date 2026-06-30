<?php

namespace App\Support;

use App\Models\ChurchEvent;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filtres pour exclure les données des événements archivés ou clôturés des vues opérationnelles.
 */
class RetreatActiveEventScope
{
    /**
     * Événements visibles dans l'administration opérationnelle (retraite en cours).
     *
     * @param  Builder<ChurchEvent>  $query
     * @return Builder<ChurchEvent>
     */
    public static function operationalEvents(Builder $query): Builder
    {
        return $query
            ->whereNull('archived_at')
            ->where('is_publicly_closed', false);
    }

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
     * Restreint ateliers/chambres à l'événement opérationnel courant (non archivé, non clôturé).
     *
     * @param  Builder<\App\Models\RetreatAtelier|\App\Models\RetreatChambre>  $query
     * @return Builder<\App\Models\RetreatAtelier|\App\Models\RetreatChambre>
     */
    public static function applyToLogistics(Builder $query): Builder
    {
        return $query->whereHas(
            'event',
            fn (Builder $eventQuery): Builder => self::operationalEvents($eventQuery)
        );
    }

    /**
     * @param  Builder<\App\Models\RetreatAtelier>  $query
     * @return Builder<\App\Models\RetreatAtelier>
     */
    public static function applyToAteliers(Builder $query): Builder
    {
        return self::applyToLogistics($query);
    }

    /**
     * @param  Builder<\App\Models\RetreatChambre>  $query
     * @return Builder<\App\Models\RetreatChambre>
     */
    public static function applyToChambres(Builder $query): Builder
    {
        return self::applyToLogistics($query);
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
