<?php

namespace App\Filament\Resources\RetreatPolicyAcknowledgements\Pages;

use App\Filament\Resources\RetreatPolicyAcknowledgements\RetreatPolicyAcknowledgementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class ListRetreatPolicyAcknowledgements extends ListRecords
{
    protected static string $resource = RetreatPolicyAcknowledgementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
        ];
    }
}
