<?php

namespace App\Filament\Resources\RetreatParticipantDeletionLogs\Tables;

use App\Models\RetreatParticipantDeletionLog;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Zvizvi\UserFields\Components\UserColumn;

class RetreatParticipantDeletionLogsTable
{
    /**
     * @param Table $table Table Filament
     * @return Table
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                UserColumn::make('performedBy')
                    ->label('Supprimé par')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('purge_status')
                    ->label('Purge')
                    ->badge()
                    ->state(fn (RetreatParticipantDeletionLog $record): string => $record->isPurgeable()
                        ? 'Supprimable'
                        : 'Conservé 1 mois')
                    ->color(fn (RetreatParticipantDeletionLog $record): string => $record->isPurgeable()
                        ? 'success'
                        : 'warning')
                    ->description(fn (RetreatParticipantDeletionLog $record): ?string => $record->isPurgeable()
                        ? null
                        : 'Suppression possible le '.$record->purgeableAt()?->format('d/m/Y H:i')),
                TextColumn::make('participant_count')
                    ->label('Participants')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('event.name')
                    ->label('Événement')
                    ->placeholder('Plusieurs / —')
                    ->toggleable(),
                TextColumn::make('participants_summary')
                    ->label('Participants supprimés')
                    ->limit(80)
                    ->tooltip(fn (RetreatParticipantDeletionLog $record): string => $record->participants_summary)
                    ->wrap(),
                TextColumn::make('related_summary')
                    ->label('Données liées')
                    ->limit(60)
                    ->tooltip(fn (RetreatParticipantDeletionLog $record): string => $record->related_summary)
                    ->toggleable(),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make()
                    ->label('Supprimer l\'entrée')
                    ->visible(fn (RetreatParticipantDeletionLog $record): bool => $record->isPurgeable())
                    ->modalHeading('Supprimer cette entrée d\'historique')
                    ->modalDescription('Cette action efface définitivement le journal de suppression. Les participants supprimés ne seront pas restaurés.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('purge_historique')
                        ->label('Supprimer l\'historique sélectionné')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Purger l\'historique sélectionné')
                        ->modalDescription('Seules les entrées datant de plus d\'un mois seront supprimées. Les plus récentes seront ignorées.')
                        ->modalSubmitActionLabel('Supprimer les entrées éligibles')
                        ->action(function (Collection $records): void {
                            $purgeable = $records->filter(
                                fn (RetreatParticipantDeletionLog $record): bool => $record->isPurgeable()
                            );
                            $blockedCount = $records->count() - $purgeable->count();

                            if ($purgeable->isEmpty()) {
                                Notification::make()
                                    ->title('Aucune entrée supprimée')
                                    ->body('L\'historique ne peut être purgé qu\'après 1 mois de conservation.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $purgeable->each(fn (RetreatParticipantDeletionLog $record) => $record->delete());

                            $body = sprintf('%d entrée(s) d\'historique supprimée(s).', $purgeable->count());

                            if ($blockedCount > 0) {
                                $body .= sprintf(' %d entrée(s) ignorée(s) (moins d\'un mois).', $blockedCount);
                            }

                            Notification::make()
                                ->title('Historique purgé')
                                ->body($body)
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->authorize('deleteAny', RetreatParticipantDeletionLog::class),
                ]),
            ]);
    }
}
