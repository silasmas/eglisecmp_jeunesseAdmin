<?php

namespace App\Filament\Resources\RetreatActivityPlans\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RetreatActivityPlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make("Detail du plan d'activite")
                    ->schema([
                        TextEntry::make('session.title')->label('Session')->placeholder('-'),
                        TextEntry::make('title')->label('Titre'),
                        TextEntry::make('activity_type')->label("Type d'activite"),
                        TextEntry::make('starts_at')->label('Debut')->time('H:i'),
                        TextEntry::make('ends_at')->label('Fin')->time('H:i'),
                        TextEntry::make('location')->label('Lieu')->placeholder('-'),
                        IconEntry::make('is_mandatory')->label('Obligatoire')->boolean(),
                        TextEntry::make('status')->label('Statut'),
                        TextEntry::make('notes')->label('Notes')->placeholder('-')->columnSpanFull(),
                        IconEntry::make('is_active')->label('Active')->boolean(),
                    ])
                    ->columns(2),
            ]);
    }
}
