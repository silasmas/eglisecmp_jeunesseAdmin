<?php

namespace App\Filament\Resources\RetreatSessions\Schemas;

use App\Models\ChurchEvent;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RetreatSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Configuration session')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')
                            ->label('Titre')
                            ->helperText('Nom visible de la session.')
                            ->required(),
                        DateTimePicker::make('start_at')
                            ->label('Debut')
                            ->helperText('Date et heure de debut de la session.')
                            ->required(),
                        DateTimePicker::make('end_at')
                            ->label('Fin')
                            ->helperText('Date et heure de fin de la session.')
                            ->required(),
                        TextInput::make('room')
                            ->label('Salle / zone')
                            ->helperText('Lieu physique de la session.')
                            ->required(),
                        Select::make('event_id')
                            ->label('Evenement')
                            ->options(fn (callable $get): array => ChurchEvent::query()
                                ->where(function ($query) use ($get): void {
                                    $query
                                        ->where(function ($q): void {
                                            $q->where('is_active', true)->where('start_at', '>=', now());
                                        })
                                        ->orWhere('id', $get('event_id'));
                                })
                                ->orderBy('start_at')
                                ->get()
                                ->mapWithKeys(fn (ChurchEvent $record): array => [
                                    $record->id => "{$record->name} ({$record->start_at?->format('d/m/Y')})",
                                ])
                                ->all())
                            ->helperText('Selection obligatoire: uniquement les evenements actifs avec une date future.')
                            ->searchable()
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Session active')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
