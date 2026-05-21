<?php

namespace App\Filament\Resources\RetreatNotifications\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RetreatNotificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Message')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Titre')
                            ->placeholder('-'),
                        TextEntry::make('category')
                            ->label('Categorie')
                            ->badge(),
                        TextEntry::make('message')
                            ->label('Contenu')
                            ->columnSpanFull(),
                        TextEntry::make('link')
                            ->label('Lien')
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Ciblage et etat')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Utilisateur')
                            ->placeholder('-'),
                        TextEntry::make('subject_type')
                            ->label('Type sujet')
                            ->placeholder('-'),
                        TextEntry::make('subject_id')
                            ->label('ID sujet')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('laravel_notification_id')
                            ->label('ID notification Laravel')
                            ->placeholder('-'),
                        IconEntry::make('is_read')
                            ->label('Lu')
                            ->boolean(),
                        IconEntry::make('is_active')
                            ->label('Actif')
                            ->boolean(),
                    ])
                    ->columns(2),
            ]);
    }
}
