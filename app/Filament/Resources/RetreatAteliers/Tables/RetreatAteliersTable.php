<?php

namespace App\Filament\Resources\RetreatAteliers\Tables;

use App\Filament\Support\ResendStaffAccessCredentialsFilamentAction;
use App\Filament\Support\RetreatAtelierAgeMismatchFilamentActions;
use App\Filament\Tables\Columns\UserStackedColumn;
use App\Services\RetreatPlacementAssignmentService;
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

class RetreatAteliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => RetreatActiveEventScope::applyToAteliers($query)
                ->with(['responsable', 'event', 'participants'])
                ->withCount([
                    'participants as participants_count' => fn ($participantQuery) => RetreatActiveEventScope::applyToParticipantCount($participantQuery),
                ]))
            ->columns([
                TextColumn::make('event.name')
                    ->label('Retraite')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('numero')
                    ->label('Numero atelier')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('age_range')
                    ->label('Tranche d\'age')
                    ->state(fn ($record): string => self::formatAgeRange($record))
                    ->badge()
                    ->color(fn ($record): string => filled($record->age_min) || filled($record->age_max) ? 'info' : 'gray'),
                TextColumn::make('age_mismatch_count')
                    ->label('Mauvaise affectation')
                    ->state(fn ($record): int => app(RetreatPlacementAssignmentService::class)
                        ->countMismatchedParticipantsForAtelier($record))
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn (int $state): string => $state > 0
                        ? sprintf('%d hors tranche', $state)
                        : 'OK')
                    ->tooltip(fn ($record): string => app(RetreatPlacementAssignmentService::class)
                        ->summarizeMismatchedParticipantsForAtelier($record)),
                UserColumn::make('responsable')
                    ->label('Responsable')
                    ->wrapped(),
                TextColumn::make('role_on_atelier')
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
                RetreatAtelierAgeMismatchFilamentActions::reassignIntelligentlyAction(),
                RetreatAtelierAgeMismatchFilamentActions::quarantineOnlyAction(),
                EditAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @param mixed $record Atelier
     * @return string Libelle tranche d'age
     */
    protected static function formatAgeRange($record): string
    {
        if (filled($record->age_min) && filled($record->age_max)) {
            return "{$record->age_min}–{$record->age_max} ans";
        }

        if (filled($record->age_min)) {
            return "≥{$record->age_min} ans";
        }

        if (filled($record->age_max)) {
            return "≤{$record->age_max} ans";
        }

        return '—';
    }
}
