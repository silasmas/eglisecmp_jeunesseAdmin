<?php

namespace App\Filament\Resources\RetreatParticipantDeletionLogs\Pages;

use App\Filament\Resources\RetreatParticipantDeletionLogs\RetreatParticipantDeletionLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatParticipantDeletionLog extends ViewRecord
{
    protected static string $resource = RetreatParticipantDeletionLogResource::class;

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Supprimer l\'entrée')
                ->visible(fn (): bool => $this->record->isPurgeable())
                ->modalHeading('Supprimer cette entrée d\'historique')
                ->modalDescription('Cette action efface définitivement le journal. Les participants supprimés ne seront pas restaurés.')
                ->successRedirectUrl(RetreatParticipantDeletionLogResource::getUrl('index')),
        ];
    }
}
