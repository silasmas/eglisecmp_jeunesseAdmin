<?php

namespace App\Filament\Resources\RetreatChambres\Tables;

use App\Filament\Tables\Columns\UserStackedColumn;
use App\Filament\Support\ResendStaffAccessCredentialsFilamentAction;
use App\Support\RetreatActiveEventScope;
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

class RetreatChambresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => RetreatActiveEventScope::applyToChambres($query)
                ->with(['responsable', 'event'])
                ->withCount([
                    'participants as participants_count' => fn ($participantQuery) => RetreatActiveEventScope::applyToParticipantCount($participantQuery),
                ]))
            ->columns([
                TextColumn::make('event.name')
                    ->label('Retraite')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nom')
                    ->label('Chambre')
                    ->searchable(),
                TextColumn::make('capacite')
                    ->label('Capacite')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sexe')
                    ->label('Type')
                    ->searchable(),
                UserColumn::make('responsable')
                    ->label('Responsable')
                    ->wrapped(),
                TextColumn::make('role_on_chambre')
                    ->label('Role')
                    ->badge()
                    ->searchable(),
                UserStackedColumn::make('participants')
                    ->label('Profils affectes')
                    ->limit(6)
                    ->limitedRemainingText()
                    ->tooltip(fn ($state): ?string => $state?->full_name),
                TextColumn::make('participants_count')
                    ->label('Participants')
                    ->counts('participants')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rapport_final')
                    ->label('Rapport final')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                ViewAction::make()->modal()->modalWidth(Width::FiveExtraLarge)->modalAlignment(Alignment::Center),
                ResendStaffAccessCredentialsFilamentAction::make(),
                EditAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
