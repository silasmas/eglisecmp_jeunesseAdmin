<?php

namespace App\Filament\Resources\RetreatPolicies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RetreatPoliciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event.name')
                    ->label('Evenement')
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Categorie')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),
                TextColumn::make('target_audience')
                    ->label('Public cible')
                    ->searchable(),
                TextColumn::make('severity_level')
                    ->label('Niveau severite')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_mandatory')
                    ->label('Obligatoire')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('effective_from')
                    ->label('Effective a partir de')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('effective_to')
                    ->label('Effective jusqu a')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_by')
                    ->label('Cree par')
                    ->numeric()
                    ->sortable(),
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
