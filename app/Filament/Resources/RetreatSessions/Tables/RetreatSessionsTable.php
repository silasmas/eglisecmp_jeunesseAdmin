<?php

namespace App\Filament\Resources\RetreatSessions\Tables;

use App\Filament\Resources\RetreatSessions\RetreatSessionResource;
use App\Models\RetreatSession;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Wezlo\FilamentRecordWatcher\Actions\UnwatchAction;
use Wezlo\FilamentRecordWatcher\Actions\WatchAction;

class RetreatSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),
                TextColumn::make('start_at')
                    ->label('Debut')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('room')
                    ->label('Salle / zone')
                    ->searchable(),
                TextColumn::make('event.name')
                    ->label('Evenement')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Active')
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
                ActionGroup::make([
                    ViewAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
                    EditAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
                    WatchAction::make(),
                    UnwatchAction::make(),
                    Action::make('open_in_new_tab')
                        ->label('Ouvrir dans un onglet')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (RetreatSession $record): string => RetreatSessionResource::getUrl('view', ['record' => $record]))
                        ->openUrlInNewTab(),
                ])
                    ->iconButton()
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->tooltip('Actions'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
