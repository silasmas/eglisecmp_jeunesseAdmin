<?php

namespace App\Filament\Resources\RetreatPolicies\Pages;

use App\Filament\Resources\RetreatPolicies\RetreatPolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class ListRetreatPolicies extends ListRecords
{
    protected static string $resource = RetreatPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
        ];
    }
}
