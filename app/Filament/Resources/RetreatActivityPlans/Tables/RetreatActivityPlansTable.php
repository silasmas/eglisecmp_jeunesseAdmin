<?php

namespace App\Filament\Resources\RetreatActivityPlans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Wezlo\FilamentRecordWatcher\Actions\UnwatchAction;
use Wezlo\FilamentRecordWatcher\Actions\WatchAction;

class RetreatActivityPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['session.event'])
                ->orderBy('session_id')
                ->orderBy('starts_at')
                ->orderBy('status')
            )
            ->defaultGroup(
                Group::make('session.title')
                    ->label('Session')
                    ->getTitleFromRecordUsing(fn ($record): string => $record->session?->title ?? 'Sans session')
                    ->getDescriptionFromRecordUsing(fn ($record): string => trim(($record->session?->event?->name ?? 'Evenement non defini').' - '.($record->session?->start_at?->translatedFormat('d/m/Y') ?? 'Date non definie')))
                    ->collapsible()
            )
            ->columns([
                View::make('filament.tables.columns.retreat-activity-plan-timeline-card')
                    ->components([
                        TextColumn::make('title')->searchable(),
                        TextColumn::make('session.event.name')->searchable(),
                        TextColumn::make('session.title')->searchable(),
                        TextColumn::make('activity_type')->searchable(),
                        TextColumn::make('location')->searchable(),
                        TextColumn::make('status')->searchable(),
                        TextColumn::make('notes')->searchable(),
                    ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
                EditAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
                WatchAction::make(),
                UnwatchAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10])
            ->asTimeline();
    }
}
