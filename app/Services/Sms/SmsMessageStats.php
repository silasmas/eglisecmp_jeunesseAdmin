<?php

namespace App\Services\Sms;

use App\Models\SmsMessageLog;
use Illuminate\Database\Eloquent\Builder;

/**
 * Agrège les statistiques d’envois SMS (envoyés, livrés, échecs, en attente).
 *
 * Note : le canal SMS Keccel ne fournit pas d’accusé « lu » (lecture téléphone) ;
 * seul DELIVERED / FAILED est fiable via delivery.asp.
 */
class SmsMessageStats
{
    /**
     * @param  Builder<SmsMessageLog>|null  $query  Périmètre optionnel
     * @return array{
     *     total: int,
     *     sent: int,
     *     delivered: int,
     *     failed: int,
     *     pending: int,
     *     delivery_pending: int,
     *     read: int
     * }
     */
    public function summarize(?Builder $query = null): array
    {
        $base = $query ? (clone $query) : SmsMessageLog::query();

        $total = (clone $base)->count();
        $sent = (clone $base)->where('status', 'sent')->count();
        $delivered = (clone $base)->where(function (Builder $q): void {
            $q->where('status', 'delivered')
                ->orWhereIn('delivery_status', ['DELIVERED', 'READ']);
        })->count();
        $failed = (clone $base)->where(function (Builder $q): void {
            $q->where('status', 'failed')
                ->orWhereIn('delivery_status', ['FAILED', 'ERROR', 'REJECTED', 'EXPIRED']);
        })->count();
        $pending = (clone $base)->where('status', 'pending')->count();
        $deliveryPending = (clone $base)
            ->whereNotNull('provider_reference')
            ->where(function (Builder $q): void {
                $q->whereNull('delivery_status')
                    ->orWhereIn('delivery_status', ['PENDING', 'UNKNOWN', 'BUFFERED', 'ENROUTE', 'ACCEPTED']);
            })
            ->whereIn('status', ['sent', 'pending', 'delivered'])
            ->count();
        $read = (clone $base)->where('delivery_status', 'READ')->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'delivered' => $delivered,
            'failed' => $failed,
            'pending' => $pending,
            'delivery_pending' => $deliveryPending,
            'read' => $read,
        ];
    }
}
