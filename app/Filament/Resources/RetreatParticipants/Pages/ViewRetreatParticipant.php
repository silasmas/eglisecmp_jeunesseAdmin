<?php

namespace App\Filament\Resources\RetreatParticipants\Pages;

use App\Filament\Resources\RetreatParticipants\RetreatParticipantResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatParticipant extends ViewRecord
{
    protected static string $resource = RetreatParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('marquer_badge_remis')
                ->label('Marquer badge remis')
                ->icon('heroicon-o-identification')
                ->color('success')
                ->visible(fn (): bool => (bool) $this->record->paiement_valide && ! $this->record->badge_received)
                ->requiresConfirmation()
                ->modalHeading('Confirmer la remise du badge')
                ->modalDescription('Indique que le participant a recu son badge physique sur place.')
                ->action(function (): void {
                    $this->record->update([
                        'badge_received' => true,
                        'badge_received_at' => now(),
                    ]);
                    $this->refreshFormData(['badge_received', 'badge_received_at']);
                }),
            EditAction::make(),
        ];
    }
}
