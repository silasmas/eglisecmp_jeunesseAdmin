<?php

namespace App\Filament\Resources\RetreatChambres\Schemas;

use App\Filament\Infolists\Components\UserStackedEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Zvizvi\UserFields\Components\UserEntry;

class RetreatChambreInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vue detaillee')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('nom')->label('Nom / code'),
                        TextEntry::make('capacite')->label('Capacite')->numeric(),
                        TextEntry::make('sexe')->label('Type')->placeholder('-'),
                        TextEntry::make('participants_count')
                            ->label('Participants affectes')
                            ->state(fn ($record): int => $record->participants()->count())
                            ->badge()
                            ->color('info'),
                        UserEntry::make('responsable')->label('Responsable')->placeholder('-'),
                        UserStackedEntry::make('participants')
                            ->label('Profils affectes')
                            ->state(fn ($record) => $record->participants)
                            ->limit(8)
                            ->limitedRemainingText()
                            ->tooltip(fn ($state): ?string => $state?->full_name),
                        TextEntry::make('role_on_chambre')->label('Role du responsable')->badge(),
                        TextEntry::make('description')->label('Description')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('rapport_final')->label('Rapport final')->placeholder('-')->columnSpanFull(),
                        IconEntry::make('is_active')->label('Active')->boolean(),
                    ])
                    ->columns(2),
                Section::make('Participants affectes')
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('participants_table')
                            ->label('')
                            ->view('filament.infolists.participants-assignment-table')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
