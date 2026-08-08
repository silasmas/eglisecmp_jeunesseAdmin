<?php

namespace App\Filament\Resources\SmsMessageLogs\Widgets;

use App\Services\Sms\SmsMessageStats;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Vue d’ensemble des envois SMS : volumes et accusés de réception.
 */
class SmsMessageLogsStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $stats = app(SmsMessageStats::class)->summarize();

        return [
            Stat::make('Total envois', (string) $stats['total'])
                ->description('Tous contextes')
                ->color('gray'),
            Stat::make('Envoyés (API OK)', (string) $stats['sent'])
                ->description('Acceptés par Keccel')
                ->color('info'),
            Stat::make('Arrivés (livrés)', (string) $stats['delivered'])
                ->description('Accusé DELIVERED')
                ->color('success'),
            Stat::make('Échecs', (string) $stats['failed'])
                ->description('Envoi ou livraison')
                ->color('danger'),
            Stat::make('Accusés en attente', (string) $stats['delivery_pending'])
                ->description('À vérifier via delivery.asp')
                ->color('warning'),
            Stat::make('Lu', (string) $stats['read'])
                ->description('Non fourni par SMS Keccel (souvent 0)')
                ->color('gray'),
        ];
    }
}
