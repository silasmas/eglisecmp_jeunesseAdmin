<?php

namespace App\Filament\Resources\RetreatParticipants\Pages;

use App\Filament\Resources\RetreatParticipants\RetreatParticipantResource;
use App\Filament\Support\RetreatParticipantDeletionActions;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRetreatParticipant extends EditRecord
{
    protected static string $resource = RetreatParticipantResource::class;

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            RetreatParticipantDeletionActions::singleDeleteAction()
                ->successRedirectUrl(RetreatParticipantResource::getUrl('index')),
        ];
    }
}
