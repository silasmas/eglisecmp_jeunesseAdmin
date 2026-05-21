<?php

namespace App\Filament\Resources\ChurchEvents\Widgets;

use App\Models\ChurchEvent;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChurchEventsStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total evenements', (string) ChurchEvent::query()->count()),
            Stat::make('Evenements actifs', (string) ChurchEvent::query()->where('is_active', true)->count()),
            Stat::make('A venir', (string) ChurchEvent::query()->where('start_at', '>', now())->count()),
            Stat::make('Termines', (string) ChurchEvent::query()->whereNotNull('end_at')->where('end_at', '<', now())->count()),
        ];
    }
}
