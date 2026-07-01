<?php

namespace App\Filament\Resources\ChurchEventHistories\Tables;

use App\Filament\Resources\ChurchEventHistories\Support\RestoreArchivedChurchEventAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Tableau des événements archivés (historique).
 */
class ChurchEventHistoriesTable
{
    /**
     * @param  Table  $table Table Filament
     * @return Table
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('archived_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Retraite')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('archived_at')
                    ->label('Archivée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('start_at')
                    ->label('Début')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                TextColumn::make('location')
                    ->label('Lieu')
                    ->toggleable(),
                TextColumn::make('participants_count')
                    ->label('Participants')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('ateliers_count')
                    ->label('Ateliers')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('chambres_count')
                    ->label('Chambres')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                RestoreArchivedChurchEventAction::make(),
            ]);
    }
}
