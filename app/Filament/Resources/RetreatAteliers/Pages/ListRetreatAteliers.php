<?php

namespace App\Filament\Resources\RetreatAteliers\Pages;

use App\Filament\Resources\RetreatAteliers\RetreatAtelierResource;
use App\Filament\Resources\RetreatAteliers\Widgets\RetreatAteliersStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class ListRetreatAteliers extends ListRecords
{
    protected static string $resource = RetreatAtelierResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
