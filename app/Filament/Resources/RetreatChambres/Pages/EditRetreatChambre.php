<?php

namespace App\Filament\Resources\RetreatChambres\Pages;

use App\Filament\Resources\RetreatChambres\RetreatChambreResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRetreatChambre extends EditRecord
{
    protected static string $resource = RetreatChambreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
