<?php

namespace App\Filament\Resources\RetreatParticipantDeletionLogs\Schemas;

use App\Models\RetreatParticipantDeletionLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Zvizvi\UserFields\Components\UserEntry;

class RetreatParticipantDeletionLogInfolist
{
    /**
     * @param Schema $schema Schéma Filament
     * @return Schema
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Action')
                    ->schema([
                        UserEntry::make('performedBy')
                            ->label('Supprimé par'),
                        TextEntry::make('created_at')
                            ->label('Date et heure')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('participant_count')
                            ->label('Nombre de participants'),
                        TextEntry::make('event.name')
                            ->label('Événement')
                            ->placeholder('Plusieurs événements ou non renseigné'),
                        TextEntry::make('purge_status')
                            ->label('Purge historique')
                            ->badge()
                            ->state(fn (RetreatParticipantDeletionLog $record): string => $record->isPurgeable()
                                ? 'Supprimable'
                                : 'Conservé 1 mois')
                            ->color(fn (RetreatParticipantDeletionLog $record): string => $record->isPurgeable()
                                ? 'success'
                                : 'warning')
                            ->helperText(fn (RetreatParticipantDeletionLog $record): string => $record->isPurgeable()
                                ? 'Cette entrée peut être purgée de l\'historique.'
                                : 'Suppression possible à partir du '.$record->purgeableAt()?->format('d/m/Y H:i').'.'),
                    ])
                    ->columns(2),
                Section::make('Participants supprimés (compact)')
                    ->schema([
                        TextEntry::make('participants_summary')
                            ->label('Résumé')
                            ->columnSpanFull()
                            ->prose(),
                    ]),
                Section::make('Données liées supprimées (compact)')
                    ->schema([
                        TextEntry::make('related_summary')
                            ->label('Résumé')
                            ->columnSpanFull()
                            ->prose(),
                    ]),
            ]);
    }
}
