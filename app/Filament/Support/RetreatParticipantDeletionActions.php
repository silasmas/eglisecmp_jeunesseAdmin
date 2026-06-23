<?php

namespace App\Filament\Support;

use App\Models\RetreatParticipant;
use App\Models\User;
use App\Services\RetreatParticipantDeletionService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Actions Filament de suppression de participants avec aperçu et historique.
 */
class RetreatParticipantDeletionActions
{
    /**
     * @return BulkAction Action de suppression en masse
     */
    public static function bulkDeleteAction(): BulkAction
    {
        $service = app(RetreatParticipantDeletionService::class);

        return BulkAction::make('supprimer_participants')
            ->label('Supprimer les participants')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Supprimer les participants sélectionnés')
            ->modalDescription('Seuls les participants et leurs données liées seront supprimés. Aucun événement, chambre ou atelier ne sera effacé.')
            ->modalSubmitActionLabel('Supprimer définitivement')
            ->modalContent(function (Collection $records) use ($service) {
                $participants = $records->filter(fn ($record): bool => $record instanceof RetreatParticipant);

                return $service->renderPreviewHtml($service->buildPreview($participants));
            })
            ->action(function (Collection $records) use ($service): void {
                $admin = Auth::user();

                if (! $admin instanceof User) {
                    return;
                }

                $participants = $records->filter(fn ($record): bool => $record instanceof RetreatParticipant);

                if ($participants->isEmpty()) {
                    Notification::make()
                        ->title('Suppression')
                        ->body('Aucun participant valide dans la sélection.')
                        ->warning()
                        ->send();

                    return;
                }

                $result = $service->deleteParticipants($participants, $admin);

                Notification::make()
                    ->title('Participants supprimés')
                    ->body(sprintf(
                        '%d participant(s) supprimé(s). Historique #%d enregistré.',
                        $result['deleted_count'],
                        $result['log']->id
                    ))
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion()
            ->authorize('deleteAny', RetreatParticipant::class);
    }

    /**
     * @return Action Action de suppression unitaire
     */
    public static function singleDeleteAction(): Action
    {
        $service = app(RetreatParticipantDeletionService::class);

        return Action::make('supprimer_participant')
            ->label('Supprimer')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Supprimer ce participant')
            ->modalDescription('Le participant et ses données liées seront supprimés. Un historique compact sera conservé.')
            ->modalSubmitActionLabel('Supprimer définitivement')
            ->modalContent(function (RetreatParticipant $record) use ($service) {
                return $service->renderPreviewHtml($service->buildPreview(collect([$record])));
            })
            ->action(function (RetreatParticipant $record) use ($service): void {
                $admin = Auth::user();

                if (! $admin instanceof User) {
                    return;
                }

                $result = $service->deleteParticipants(collect([$record]), $admin);

                Notification::make()
                    ->title('Participant supprimé')
                    ->body(sprintf('Historique #%d enregistré.', $result['log']->id))
                    ->success()
                    ->send();
            });
    }
}
