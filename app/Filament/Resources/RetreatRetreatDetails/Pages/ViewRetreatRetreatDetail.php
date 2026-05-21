<?php

namespace App\Filament\Resources\RetreatRetreatDetails\Pages;

use App\Filament\Resources\RetreatRetreatDetails\RetreatRetreatDetailResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatRetreatDetail extends ViewRecord
{
    protected static string $resource = RetreatRetreatDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
