<?php

namespace App\Filament\Resources\RetreatParticipants\Pages;

use App\Filament\Resources\RetreatParticipants\RetreatParticipantResource;
use App\Filament\Support\RetreatBilletPreviewFilamentAction;
use App\Models\User;
use App\Support\RetreatBilletPageBuilder;
use App\Services\RetreatParticipantRegistrationService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewRetreatParticipant extends ViewRecord
{
    protected static string $resource = RetreatParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RetreatBilletPreviewFilamentAction::make('preview_billet')
                ->label('Prévisualiser le billet')
                ->url(fn (): ?string => RetreatBilletPageBuilder::adminPreviewUrl($this->getRecord()))
                ->visible(fn (): bool => RetreatBilletPreviewFilamentAction::canPreview($this->getRecord())),
            Action::make('marquer_badge_remis')
                ->label('Marquer badge remis')
                ->icon('heroicon-o-identification')
                ->color('success')
                ->visible(fn (): bool => (bool) $this->record->paiement_valide && ! $this->record->badge_received)
                ->requiresConfirmation()
                ->modalHeading('Confirmer la remise du badge')
                ->modalDescription('Indique que le participant a recu son badge physique sur place.')
                ->action(function (): void {
                    $admin = Auth::user();
                    if (! $admin instanceof User) {
                        return;
                    }

                    app(RetreatParticipantRegistrationService::class)->markBadgeReceived($this->record, $admin);

                    Notification::make()
                        ->title('Badge marqué comme remis')
                        ->success()
                        ->send();

                    $this->refreshFormData(['badge_received', 'badge_received_at', 'badge_received_by']);
                }),
            EditAction::make(),
        ];
    }
}
