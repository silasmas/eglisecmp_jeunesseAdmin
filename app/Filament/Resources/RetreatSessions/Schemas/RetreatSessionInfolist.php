<?php

namespace App\Filament\Resources\RetreatSessions\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RetreatSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail session')
                    ->schema([
                        TextEntry::make('title')->label('Titre'),
                        TextEntry::make('start_at')->label('Debut')->dateTime(),
                        TextEntry::make('end_at')->label('Fin')->dateTime(),
                        TextEntry::make('room')->label('Salle / zone'),
                        TextEntry::make('event.name')->label('Evenement')->placeholder('-'),
                        IconEntry::make('is_active')->label('Active')->boolean(),
                    ])
                    ->columns(2),
            ]);
    }
}
