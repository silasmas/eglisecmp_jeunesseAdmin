<?php

namespace App\Filament\Resources\RetreatChambres\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Zvizvi\UserFields\Components\UserSelect;

class RetreatChambreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Configuration de la chambre')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nom')
                            ->label('Nom / code chambre')
                            ->helperText('Code court de la chambre (ex: A, B, C).')
                            ->required()
                            ->maxLength(1),
                        TextInput::make('capacite')
                            ->label('Capacite')
                            ->helperText('Nombre maximum de participants dans cette chambre.')
                            ->required()
                            ->numeric(),
                        Select::make('sexe')
                            ->label('Type de chambre')
                            ->options([
                                'homme' => 'Homme',
                                'femme' => 'Femme',
                                'mixte' => 'Mixte',
                            ])
                            ->helperText('Definit le profil de participants pouvant etre affectes.'),
                        UserSelect::make('responsable_user_id')
                            ->label('Responsable de chambre')
                            ->relationship('responsable', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Utilisateur charge de la supervision de cette chambre.'),
                        Select::make('role_on_chambre')
                            ->label('Role du responsable')
                            ->options([
                                'responsable' => 'Responsable',
                                'adjoint' => 'Adjoint',
                                'assistant' => 'Assistant',
                            ])
                            ->helperText("Precise le niveau d'intervention du responsable sur cette chambre.")
                            ->required()
                            ->default('responsable'),
                        Textarea::make('description')
                            ->label('Description')
                            ->helperText('Objectif, organisation ou contexte de la chambre.')
                            ->columnSpanFull(),
                        Textarea::make('rapport_final')
                            ->label('Rapport final')
                            ->helperText('Synthese des actions realisees, incidents et recommandations.')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Chambre active')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
