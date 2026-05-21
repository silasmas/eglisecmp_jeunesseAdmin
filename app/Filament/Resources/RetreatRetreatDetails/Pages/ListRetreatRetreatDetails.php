<?php

namespace App\Filament\Resources\RetreatRetreatDetails\Pages;

use App\Filament\Resources\RetreatRetreatDetails\RetreatRetreatDetailResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class ListRetreatRetreatDetails extends ListRecords
{
    protected static string $resource = RetreatRetreatDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
        ];
    }
}
