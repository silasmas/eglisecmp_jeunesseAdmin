<?php

namespace App\Filament\Resources\RetreatParticipantMovements\Tables;

use App\Models\RetreatParticipantMovement;
use App\Services\RetreatAtelierAuthorizationService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Zvizvi\UserFields\Components\UserColumn;

class RetreatParticipantMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => app(RetreatAtelierAuthorizationService::class)
                ->scopeMovementsForUser($query->with([
                    'participant.atelier.responsable',
                    'participant.atelier.adjoint',
                    'authorizedBy',
                    'event',
                ]), Auth::user())
                ->orderByDesc('moved_at'))
            ->groups([
                Group::make('participant.atelier.numero')
                    ->label('Atelier')
                    ->getTitleFromRecordUsing(fn (RetreatParticipantMovement $record): string => filled($record->participant?->atelier?->numero)
                        ? 'Atelier '.$record->participant->atelier->numero
                        : 'Sans atelier')
                    ->collapsible(),
            ])
            ->defaultGroup('participant.atelier.numero')
            ->columns([
                UserColumn::make('participant')
                    ->label('Participant')
                    ->wrapped(),
                TextColumn::make('participant.atelier.numero')
                    ->label('Atelier')
                    ->sortable(),
                TextColumn::make('event.name')
                    ->label('Evenement')
                    ->searchable(),
                TextColumn::make('movement_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'exit' => 'Sortie',
                        'return' => 'Retour',
                        'entrer' => 'Entrer',
                        'sorti' => 'Sorti',
                        default => $state,
                    })
                    ->badge()
                    ->searchable(),
                TextColumn::make('moved_at')
                    ->label('Date / heure')
                    ->dateTime()
                    ->sortable(),
                UserColumn::make('authorizedBy')
                    ->label('Autorise par')
                    ->wrapped(),
                TextColumn::make('reason')
                    ->label('Motif')
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
                EditAction::make()
                    ->modal()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalAlignment(Alignment::Center)
                    ->visible(fn (RetreatParticipantMovement $record): bool => app(RetreatAtelierAuthorizationService::class)
                        ->canManageParticipant(Auth::user(), $record->participant)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
