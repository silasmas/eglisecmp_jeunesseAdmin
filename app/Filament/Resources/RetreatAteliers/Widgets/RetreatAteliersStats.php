<?php

namespace App\Filament\Resources\RetreatAteliers\Widgets;

use App\Filament\Pages\ManageRetreatAtelierQuarantine;
use App\Models\RetreatAtelier;
use App\Models\RetreatParticipant;
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
            Stat::make('En quarantaine atelier', (string) RetreatParticipant::query()->where('atelier_quarantine', true)->count())
                ->description('Validation admin requise')
                ->color('warning')
                ->url(ManageRetreatAtelierQuarantine::getUrl()),
        ];
    }
}
