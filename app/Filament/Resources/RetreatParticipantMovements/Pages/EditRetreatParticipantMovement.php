<?php

namespace App\Filament\Resources\RetreatParticipantMovements\Pages;

use App\Filament\Resources\RetreatParticipantMovements\RetreatParticipantMovementResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRetreatParticipantMovement extends EditRecord
{
    protected static string $resource = RetreatParticipantMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
