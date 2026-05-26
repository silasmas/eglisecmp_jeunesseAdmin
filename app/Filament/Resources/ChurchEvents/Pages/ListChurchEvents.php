<?php

namespace App\Filament\Resources\ChurchEvents\Pages;

use App\Filament\Resources\ChurchEvents\ChurchEventResource;
use App\Filament\Resources\ChurchEvents\Widgets\ChurchEventsStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChurchEvents extends ListRecords
{
    protected static string $resource = ChurchEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ChurchEventsStats::class,
        ];
    }
}
