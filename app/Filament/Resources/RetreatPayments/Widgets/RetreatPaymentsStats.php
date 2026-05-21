<?php

namespace App\Filament\Resources\RetreatPayments\Widgets;

use App\Models\RetreatPayment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RetreatPaymentsStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $currencyStats = RetreatPayment::query()
            ->selectRaw('currency, count(*) as aggregate')
            ->groupBy('currency')
            ->orderBy('currency')
            ->pluck('aggregate', 'currency')
            ->map(fn ($count, string $currency): Stat => Stat::make("Devise {$currency}", (string) $count))
            ->values()
            ->all();

        $channelStats = RetreatPayment::query()
            ->selectRaw('channel, count(*) as aggregate')
            ->groupBy('channel')
            ->orderBy('channel')
            ->pluck('aggregate', 'channel')
            ->map(fn ($count, string $channel): Stat => Stat::make('Canal '.ucfirst(str_replace('_', ' ', $channel)), (string) $count))
            ->values()
            ->all();

        return [
            Stat::make('Nombre de paiements', (string) RetreatPayment::query()->count()),
            Stat::make('Paiements valides', (string) RetreatPayment::query()->where('etat', 'payee')->count()),
            ...$currencyStats,
            ...$channelStats,
        ];
    }
}
