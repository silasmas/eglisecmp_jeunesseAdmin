<?php

namespace App\Filament\Resources\RetreatSessions\Pages;

use App\Filament\Resources\RetreatSessions\RetreatSessionResource;
use App\Filament\Resources\RetreatSessions\Widgets\RetreatSessionsStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class ListRetreatSessions extends ListRecords
{
    protected static string $resource = RetreatSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RetreatSessionsStats::class,
        ];
    }
}
