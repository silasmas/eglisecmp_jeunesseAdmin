<?php

namespace App\Filament\Resources\RetreatParticipants\Pages;

use App\Filament\Resources\RetreatParticipants\RetreatParticipantResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRetreatParticipant extends EditRecord
{
    protected static string $resource = RetreatParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
