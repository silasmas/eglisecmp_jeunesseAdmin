<?php

namespace App\Filament\Resources\RetreatParticipantDeletionLogs\Tables;

use App\Models\RetreatParticipantDeletionLog;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
            ]);
    }
}
