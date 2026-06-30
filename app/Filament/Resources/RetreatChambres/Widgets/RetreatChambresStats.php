<?php

namespace App\Filament\Resources\RetreatChambres\Widgets;

use App\Filament\Resources\RetreatChambres\RetreatChambreResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Statistiques chambres limitées à l'événement opérationnel courant.
 */
class RetreatChambresStats extends StatsOverviewWidget
{
    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $query = RetreatChambreResource::getEloquentQuery();

        return [
            Stat::make('Total chambres', (string) (clone $query)->count()),
            Stat::make('Capacite globale', (string) (clone $query)->sum('capacite')),
            Stat::make('Chambres actives', (string) (clone $query)->where('is_active', true)->count()),
            Stat::make('Sans responsable', (string) (clone $query)->whereNull('responsable_user_id')->count()),
            Stat::make('Rapports finalises', (string) (clone $query)->whereNotNull('rapport_final')->count()),
        ];
    }
}
