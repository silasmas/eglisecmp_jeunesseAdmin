<?php

namespace App\Filament\Resources\RetreatAteliers\Pages;

use App\Filament\Resources\RetreatAteliers\RetreatAtelierResource;
use App\Services\RetreatAtelierQuarantineNotifier;
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
            Action::make('reassignMismatched')
                ->label('Réaffecter participants hors tranche')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible($mismatchCount > 0)
                ->requiresConfirmation()
                ->modalHeading('Réaffecter les participants hors tranche')
                ->modalDescription(fn (): string => sprintf(
                    '%d participant(s) de l\'atelier n°%s ne correspondent pas à la tranche %s. Ils seront réaffectés automatiquement ou placés en quarantaine.',
                    $mismatchCount,
                    $this->getRecord()->numero,
                    $placement->describeAtelierAgeRange($this->getRecord()),
                ))
                ->action(function () use ($placement): void {
                    $atelier = $this->getRecord();
                    $stats = $placement->reassignMismatchedAtelierParticipants($atelier);

                    app(RetreatAtelierQuarantineNotifier::class)->notifySuperAdminsReassignmentSummary(
                        $stats,
                        sprintf('Atelier n°%s', $atelier->numero),
                    );

                    Notification::make()
                        ->title('Réaffectation terminée')
                        ->body(sprintf(
                            '%d réaffecté(s), %d en quarantaine, %d inchangé(s).',
                            $stats['reassigned'],
                            $stats['quarantined'],
                            $stats['skipped'],
                        ))
                        ->success()
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}
