<?php

namespace App\Filament\Resources\RetreatChambres\Widgets;

use App\Models\RetreatChambre;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RetreatChambresStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total chambres', (string) RetreatChambre::query()->count()),
            Stat::make('Capacite globale', (string) RetreatChambre::query()->sum('capacite')),
            Stat::make('Chambres actives', (string) RetreatChambre::query()->where('is_active', true)->count()),
            Stat::make('Sans responsable', (string) RetreatChambre::query()->whereNull('responsable_user_id')->count()),
            Stat::make('Rapports finalises', (string) RetreatChambre::query()->whereNotNull('rapport_final')->count()),
        ];
    }
}
