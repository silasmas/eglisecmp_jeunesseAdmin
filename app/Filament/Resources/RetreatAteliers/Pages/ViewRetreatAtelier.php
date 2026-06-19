<?php

namespace App\Filament\Resources\RetreatAteliers\Pages;

use App\Filament\Pages\ManageRetreatAtelierQuarantine;
use App\Filament\Resources\RetreatAteliers\RetreatAtelierResource;
use App\Services\RetreatPlacementAssignmentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatAtelier extends ViewRecord
{
    protected static string $resource = RetreatAtelierResource::class;

    protected function getHeaderActions(): array
    {
        $placement = app(RetreatPlacementAssignmentService::class);
        $mismatchCount = $placement->countMismatchedParticipantsForAtelier($this->getRecord());

        return [
            Action::make('moveMismatchedToQuarantine')
                ->label('Mettre hors tranche en quarantaine')
                ->icon('heroicon-o-shield-exclamation')
                ->color('warning')
                ->visible($mismatchCount > 0)
                ->requiresConfirmation()
                ->modalHeading('Mettre en quarantaine les participants hors tranche')
                ->modalDescription(fn (): string => sprintf(
                    '%d participant(s) ne correspondent pas à la tranche %s. Ils seront retirés de cet atelier et placés en quarantaine. Validez ensuite leur affectation dans « Quarantaine ateliers ».',
                    $mismatchCount,
                    $placement->describeAtelierAgeRange($this->getRecord()),
                ))
                ->action(function () use ($placement): void {
                    $atelier = $this->getRecord();
                    $stats = $placement->reassignMismatchedAtelierParticipants($atelier);

                    Notification::make()
                        ->title('Participants placés en quarantaine')
                        ->body(sprintf(
                            '%d participant(s) en quarantaine — %d inchangé(s). Ouvrez « Quarantaine ateliers » pour valider les propositions.',
                            $stats['quarantined'],
                            $stats['skipped'],
                        ))
                        ->actions([
                            Action::make('openQuarantine')
                                ->label('Ouvrir quarantaine')
                                ->url(ManageRetreatAtelierQuarantine::getUrl()),
                        ])
                        ->warning()
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}
