<?php

namespace App\Filament\Resources\RetreatParticipantMovements\Pages;

use App\Filament\Resources\RetreatParticipantMovements\RetreatParticipantMovementResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatParticipantMovement extends ViewRecord
{
    protected static string $resource = RetreatParticipantMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
