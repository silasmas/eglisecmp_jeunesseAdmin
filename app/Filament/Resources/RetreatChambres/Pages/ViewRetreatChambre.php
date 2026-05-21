<?php

namespace App\Filament\Resources\RetreatChambres\Pages;

use App\Filament\Resources\RetreatChambres\RetreatChambreResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatChambre extends ViewRecord
{
    protected static string $resource = RetreatChambreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
