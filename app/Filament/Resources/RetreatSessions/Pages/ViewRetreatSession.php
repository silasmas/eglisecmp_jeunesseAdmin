<?php

namespace App\Filament\Resources\RetreatSessions\Pages;

use App\Filament\Resources\RetreatSessions\RetreatSessionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatSession extends ViewRecord
{
    protected static string $resource = RetreatSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
