<?php

namespace App\Filament\Support;

use App\Filament\Pages\ManageRetreatAtelierQuarantine;
use App\Models\RetreatAtelier;
use App\Services\RetreatPlacementAssignmentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Actions Filament pour corriger les mauvaises affectations d'âge dans un atelier.
 */
class RetreatAtelierAgeMismatchFilamentActions
{
    /**
     * Réaffectation intelligente (atelier compatible) puis quarantaine si aucun atelier.
     *
     * @return Action
     */
    public static function reassignIntelligentlyAction(): Action
    {
        return Action::make('reassignMismatchedIntelligently')
            ->label('Réaffecter hors tranche')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('warning')
            ->visible(fn (RetreatAtelier $record): bool => self::mismatchCount($record) > 0)
            ->requiresConfirmation()
            ->modalHeading('Réaffectation intelligente des hors tranche')
            ->modalDescription(fn (RetreatAtelier $record): string => sprintf(
                '%d participant(s) ne correspondent pas à la tranche %s. Le système tentera de les placer dans un atelier compatible ; les autres iront en quarantaine avec propositions.',
                self::mismatchCount($record),
                self::service()->describeAtelierAgeRange($record),
            ))
            ->action(function (RetreatAtelier $record): void {
                $stats = self::service()->reassignMismatchedAtelierParticipants($record, preferAutoReassign: true);
                self::notifyResult($stats, intelligent: true);
            });
    }

    /**
     * Mise en quarantaine sans réaffectation automatique.
     *
     * @return Action
     */
    public static function quarantineOnlyAction(): Action
    {
        return Action::make('moveMismatchedToQuarantine')
            ->label('Hors tranche → quarantaine')
            ->icon('heroicon-o-shield-exclamation')
            ->color('danger')
            ->visible(fn (RetreatAtelier $record): bool => self::mismatchCount($record) > 0)
            ->requiresConfirmation()
            ->modalHeading('Mettre les hors tranche en quarantaine')
            ->modalDescription(fn (RetreatAtelier $record): string => sprintf(
                '%d participant(s) seront retirés de l’atelier n°%s et placés en quarantaine pour validation manuelle (propositions intelligentes).',
                self::mismatchCount($record),
                $record->numero,
            ))
            ->action(function (RetreatAtelier $record): void {
                $stats = self::service()->quarantineMismatchedAtelierParticipants($record);
                self::notifyResult($stats, intelligent: false);
            });
    }

    /**
     * @param RetreatAtelier $atelier Atelier
     * @return int
     */
    protected static function mismatchCount(RetreatAtelier $atelier): int
    {
        return self::service()->countMismatchedParticipantsForAtelier($atelier);
    }

    /**
     * @return RetreatPlacementAssignmentService
     */
    protected static function service(): RetreatPlacementAssignmentService
    {
        return app(RetreatPlacementAssignmentService::class);
    }

    /**
     * @param array{reassigned: int, quarantined: int, skipped: int} $stats Résultat
     * @param bool $intelligent Mode réaffectation intelligente
     * @return void
     */
    protected static function notifyResult(array $stats, bool $intelligent): void
    {
        $body = $intelligent
            ? sprintf(
                '%d réaffecté(s) automatiquement — %d en quarantaine — %d déjà conformes.',
                $stats['reassigned'],
                $stats['quarantined'],
                $stats['skipped'],
            )
            : sprintf(
                '%d mis en quarantaine — %d déjà conformes. Validez les propositions dans « Quarantaine ateliers ».',
                $stats['quarantined'],
                $stats['skipped'],
            );

        $notification = Notification::make()
            ->title($intelligent ? 'Réaffectation terminée' : 'Quarantaine mise à jour')
            ->body($body)
            ->actions([
                Action::make('openQuarantine')
                    ->label('Ouvrir quarantaine')
                    ->url(ManageRetreatAtelierQuarantine::getUrl()),
            ]);

        if (($stats['quarantined'] ?? 0) > 0) {
            $notification->warning()->send();

            return;
        }

        $notification->success()->send();
    }
}
