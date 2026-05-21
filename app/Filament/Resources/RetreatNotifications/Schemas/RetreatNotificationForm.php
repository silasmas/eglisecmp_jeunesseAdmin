<?php

namespace App\Filament\Resources\RetreatNotifications\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Zvizvi\UserFields\Components\UserSelect;

class RetreatNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contenu')
                    ->schema([
                        TextInput::make('title')
                            ->label('Titre'),
                        Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('category')
                            ->label('Categorie')
                            ->required()
                            ->default('info'),
                        TextInput::make('link')
                            ->label('Lien'),
                    ])
                    ->columns(2),
                Section::make('Ciblage')
                    ->schema([
                        UserSelect::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->searchable(),
                        TextInput::make('subject_type')
                            ->label('Type sujet'),
                        TextInput::make('subject_id')
                            ->label('ID sujet')
                            ->numeric(),
                        TextInput::make('laravel_notification_id')
                            ->label('ID notification Laravel'),
                    ])
                    ->columns(2),
                Section::make('Etat')
                    ->schema([
                        Toggle::make('is_read')
                            ->label('Lu')
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Actif')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
