<?php

namespace App\Filament\Resources\RetreatPolicyAcknowledgements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Zvizvi\UserFields\Components\UserColumn;

class RetreatPolicyAcknowledgementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('policy.title')
                    ->label('Politique')
                    ->searchable(),
                UserColumn::make('user')
                    ->label('Utilisateur')
                    ->wrapped(),
                UserColumn::make('participant')
                    ->label('Participant')
                    ->wrapped(),
                IconColumn::make('has_read')
                    ->label('A lu')
                    ->boolean(),
                IconColumn::make('has_accepted')
                    ->label('A accepte')
                    ->boolean(),
                TextColumn::make('acknowledged_at')
                    ->label('Date accuse')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('signature_type')
                    ->label('Type signature')
                    ->searchable(),
                TextColumn::make('ip_address')
                    ->label('Adresse IP')
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
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
                EditAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
