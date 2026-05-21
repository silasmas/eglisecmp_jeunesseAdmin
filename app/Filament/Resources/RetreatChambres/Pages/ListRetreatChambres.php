<?php

namespace App\Filament\Resources\RetreatChambres\Pages;

use App\Filament\Resources\RetreatChambres\RetreatChambreResource;
use App\Filament\Resources\RetreatChambres\Widgets\RetreatChambresStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class ListRetreatChambres extends ListRecords
{
    protected static string $resource = RetreatChambreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RetreatChambresStats::class,
        ];
    }
}
