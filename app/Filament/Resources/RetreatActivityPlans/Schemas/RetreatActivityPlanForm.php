<?php

namespace App\Filament\Resources\RetreatActivityPlans\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RetreatActivityPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make("Planification d'activite")
                    ->columnSpanFull()
                    ->schema([
                        Select::make('session_id')
                            ->label('Session')
                            ->relationship('session', 'title')
                            ->searchable()
                            ->required()
                            ->helperText('Rattacher cette activite a une session.'),
                        TextInput::make('title')
                            ->label("Titre de l'activite")
                            ->required(),
                        Select::make('activity_type')
                            ->label("Type d'activite")
                            ->options([
                                'enseignement' => 'Enseignement',
                                'priere' => 'Priere',
                                'atelier' => 'Atelier',
                                'service' => 'Service',
                                'autre' => 'Autre',
                            ])
                            ->required(),
                        TimePicker::make('starts_at')
                            ->label('Debut prevu (heure)')
                            ->seconds(false)
                            ->required(),
                        TimePicker::make('ends_at')
                            ->label('Fin prevue (heure)')
                            ->seconds(false)
                            ->required(),
                        TextInput::make('location')
                            ->label('Lieu / salle')
                            ->helperText("Lieu concret ou se deroule l'activite."),
                        Select::make('status')
                            ->label('Statut')
                            ->options([
                                'planned' => 'Planifiee',
                                'ongoing' => 'En cours',
                                'done' => 'Terminee',
                                'cancelled' => 'Annulee',
                            ])
                            ->required()
                            ->default('planned'),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->helperText('Consignes ou informations importantes.'),
                        Toggle::make('is_mandatory')
                            ->label('Presence obligatoire')
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->required(),

                    ])
                    ->columns(2),
            ]);
    }
}
