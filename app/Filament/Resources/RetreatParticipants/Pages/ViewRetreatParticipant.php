<?php

namespace App\Filament\Resources\RetreatParticipants\Pages;

use App\Filament\Resources\RetreatParticipants\RetreatParticipantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatParticipant extends ViewRecord
{
    protected static string $resource = RetreatParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
