<?php

namespace App\Filament\Resources\RetreatAteliers\Widgets;

use App\Filament\Pages\ManageRetreatAtelierQuarantine;
use App\Filament\Resources\RetreatParticipants\RetreatParticipantResource;
use App\Models\RetreatAtelier;
use App\Support\RetreatActiveEventScope;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Statistiques ateliers limitées à l'événement opérationnel courant.
 */
class RetreatAteliersStats extends StatsOverviewWidget
{
    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $query = RetreatActiveEventScope::applyToAteliers(RetreatAtelier::query());

        return [
            Stat::make('Total ateliers', (string) (clone $query)->count()),
            Stat::make('Ateliers actifs', (string) (clone $query)->where('is_active', true)->count()),
            Stat::make('Sans responsable', (string) (clone $query)->whereNull('responsable_user_id')->count()),
            Stat::make('En quarantaine atelier', (string) RetreatParticipantResource::getEloquentQuery()->where('atelier_quarantine', true)->count())
                ->description('Validation admin requise')
                ->color('warning')
                ->url(ManageRetreatAtelierQuarantine::getUrl()),
        ];
    }
}
