<?php

namespace App\Filament\Resources\RetreatAteliers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Zvizvi\UserFields\Components\UserSelect;

class RetreatAtelierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make("Configuration de l'atelier")
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('numero')
                            ->label('Numero atelier')
                            ->helperText('Numero unique de reference de cet atelier.')
                            ->required()
                            ->numeric(),
                        TextInput::make('age_min')
                            ->label('Age minimum')
                            ->helperText('Tranche d\'age cible (inclus). Laissez vide si non limite.')
                            ->numeric()
                            ->minValue(15)
                            ->maxValue(99),
                        TextInput::make('age_max')
                            ->label('Age maximum')
                            ->helperText('Tranche d\'age cible (inclus). Doit etre >= age minimum.')
                            ->numeric()
                            ->minValue(15)
                            ->maxValue(99)
                            ->gte('age_min'),
                        UserSelect::make('responsable_user_id')
                            ->label("Responsable d'atelier")
                            ->relationship('responsable', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText("Personne chargee de piloter l'atelier.")
                            ->required(),
                        UserSelect::make('adjoint_user_id')
                            ->label("Adjoint d'atelier")
                            ->relationship('adjoint', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Peut aussi marquer les presences et mouvements de l\'atelier.'),
                        Select::make('role_on_atelier')
                            ->label('Role du responsable')
                            ->options([
                                'responsable' => 'Responsable',
                                'adjoint' => 'Adjoint',
                                'assistant' => 'Assistant',
                            ])
                            ->helperText('Precise le role metier du responsable sur cet atelier.')
                            ->required()
                            ->default('responsable'),
                        Textarea::make('description')
                            ->label('Description')
                            ->helperText('Objectif et organisation de cet atelier.')
                            ->columnSpanFull(),
                        Textarea::make('rapport_final')
                            ->label('Rapport final')
                            ->helperText('Bilan final des activites realisees dans cet atelier.')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Atelier actif')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
