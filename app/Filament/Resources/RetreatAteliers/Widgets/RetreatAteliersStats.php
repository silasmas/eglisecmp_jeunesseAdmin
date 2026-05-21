<?php

namespace App\Filament\Resources\RetreatAteliers\Widgets;

use App\Models\RetreatAtelier;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RetreatAteliersStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total ateliers', (string) RetreatAtelier::query()->count()),
            Stat::make('Ateliers actifs', (string) RetreatAtelier::query()->where('is_active', true)->count()),
            Stat::make('Sans responsable', (string) RetreatAtelier::query()->whereNull('responsable_user_id')->count()),
            Stat::make('Rapports finalises', (string) RetreatAtelier::query()->whereNotNull('rapport_final')->count()),
        ];
    }
}
