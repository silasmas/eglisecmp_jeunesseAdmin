<?php

namespace App\Filament\Resources\RetreatAteliers\Pages;

use App\Filament\Resources\RetreatAteliers\RetreatAtelierResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatAtelier extends ViewRecord
{
    protected static string $resource = RetreatAtelierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
