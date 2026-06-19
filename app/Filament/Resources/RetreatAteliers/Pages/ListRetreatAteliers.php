<?php

namespace App\Filament\Resources\RetreatAteliers\Pages;

use App\Filament\Pages\ManageRetreatAtelierQuarantine;
use App\Filament\Resources\RetreatAteliers\RetreatAtelierResource;
use App\Filament\Resources\RetreatAteliers\Widgets\RetreatAteliersStats;
use App\Models\RetreatParticipant;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class ListRetreatAteliers extends ListRecords
{
    protected static string $resource = RetreatAtelierResource::class;

    protected function getHeaderActions(): array
    {
        $quarantineCount = RetreatParticipant::query()->where('atelier_quarantine', true)->count();

        return [
            Action::make('openAtelierQuarantine')
                ->label('Quarantaine atelier')
                ->icon('heroicon-o-shield-exclamation')
                ->color('warning')
                ->url(fn (): string => ManageRetreatAtelierQuarantine::getUrl())
                ->badge($quarantineCount > 0 ? (string) $quarantineCount : null),
            CreateAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RetreatAteliersStats::class,
        ];
    }
}
