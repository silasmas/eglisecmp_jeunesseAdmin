<?php

namespace App\Filament\Resources\RetreatPayments\Widgets;

use App\Models\RetreatPayment;
use App\Support\RetreatActiveEventScope;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RetreatPaymentsStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $base = RetreatActiveEventScope::applyToPayments(RetreatPayment::query());

        $currencyStats = (clone $base)
            ->selectRaw('currency, count(*) as aggregate')
            ->groupBy('currency')
            ->orderBy('currency')
            ->pluck('aggregate', 'currency')
            ->map(fn ($count, string $currency): Stat => Stat::make("Devise {$currency}", (string) $count))
            ->values()
            ->all();

        $channelStats = (clone $base)
            ->selectRaw('channel, count(*) as aggregate')
            ->groupBy('channel')
            ->orderBy('channel')
            ->pluck('aggregate', 'channel')
            ->map(fn ($count, string $channel): Stat => Stat::make('Canal '.ucfirst(str_replace('_', ' ', $channel)), (string) $count))
            ->values()
            ->all();

        return [
            Stat::make('Nombre de paiements', (string) (clone $base)->count()),
            Stat::make('Paiements valides', (string) (clone $base)->where('etat', 'payee')->count()),
            ...$currencyStats,
            ...$channelStats,
        ];
    }
}
