<?php

namespace App\Filament\Resources\RetreatNotifications\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Zvizvi\UserFields\Components\UserColumn;

class RetreatNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),
                TextColumn::make('message')
                    ->label('Message')
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Categorie')
                    ->searchable(),
                TextColumn::make('link')
                    ->label('Lien')
                    ->searchable(),
                IconColumn::make('is_read')
                    ->label('Lu')
                    ->boolean(),
                UserColumn::make('user')
                    ->label('Utilisateur')
                    ->wrapped(),
                TextColumn::make('laravel_notification_id')
                    ->label('ID notification Laravel')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Cree le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Mis a jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subject_type')
                    ->label('Type sujet')
                    ->searchable(),
                TextColumn::make('subject_id')
                    ->label('ID sujet')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
