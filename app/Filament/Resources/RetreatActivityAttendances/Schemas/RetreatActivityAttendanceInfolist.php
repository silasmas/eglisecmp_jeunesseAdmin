<?php

namespace App\Filament\Resources\RetreatActivityAttendances\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RetreatActivityAttendanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail pointage')
                    ->schema([
                        ImageEntry::make('participant.photo')
                            ->label('Photo participant')
                            ->circular()
                            ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name='.urlencode((string) $record->participant?->full_name).'&background=7b1d3e&color=fff'),
                        TextEntry::make('participant.full_name')->label('Participant'),
                        TextEntry::make('activityPlan.title')->label('Activite'),
                        TextEntry::make('status')->label('Statut')->badge(),
                        TextEntry::make('check_in_at')->label("Heure d'entree")->dateTime()->placeholder('-'),
                        TextEntry::make('check_out_at')->label('Heure de sortie')->dateTime()->placeholder('-'),
                        TextEntry::make('scan_source')->label('Source'),
                        TextEntry::make('recorder.name')->label('Enregistre par')->placeholder('-'),
                        TextEntry::make('note')->label('Note')->placeholder('-')->columnSpanFull(),
                        IconEntry::make('is_active')->label('Actif')->boolean(),
                    ])
                    ->columns(2),
            ]);
    }
}
