<?php

namespace App\Filament\Resources\RetreatAteliers\Schemas;

use App\Filament\Infolists\Components\UserStackedEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Zvizvi\UserFields\Components\UserEntry;

class RetreatAtelierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vue detaillee')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('numero')->label('Numero')->numeric(),
                        TextEntry::make('age_range')
                            ->label('Tranche d\'age')
                            ->state(fn ($record): string => self::formatAgeRange($record))
                            ->badge()
                            ->color(fn ($record): string => filled($record->age_min) || filled($record->age_max) ? 'info' : 'gray'),
                        UserEntry::make('responsable')->label('Responsable')->placeholder('-'),
                        TextEntry::make('participants_count')
                            ->label('Participants affectes')
                            ->state(fn ($record): int => $record->participants()->count())
                            ->badge()
                            ->color('info'),
                        UserStackedEntry::make('participants')
                            ->label('Profils affectes')
                            ->state(fn ($record) => $record->participants)
                            ->limit(8)
                            ->limitedRemainingText()
                            ->tooltip(fn ($state): ?string => $state?->full_name),
                        TextEntry::make('role_on_atelier')->label('Role du responsable')->badge(),
                        TextEntry::make('description')->label('Description')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('rapport_final')->label('Rapport final')->placeholder('-')->columnSpanFull(),
                        IconEntry::make('is_active')->label('Actif')->boolean(),
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

    /**
     * @param mixed $record Atelier
     * @return string Libelle tranche d'age
     */
    protected static function formatAgeRange($record): string
    {
        if (filled($record->age_min) && filled($record->age_max)) {
            return "{$record->age_min} – {$record->age_max} ans";
        }

        if (filled($record->age_min)) {
            return "≥ {$record->age_min} ans";
        }

        if (filled($record->age_max)) {
            return "≤ {$record->age_max} ans";
        }

        return 'Non definie';
    }
}
