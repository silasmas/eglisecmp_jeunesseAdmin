<?php

namespace App\Filament\Resources\RetreatPolicies\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RetreatPolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->label('Evenement')
                    ->helperText("Evenement auquel cette politique s'applique.")
                    ->relationship('event', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('category')
                    ->label('Categorie')
                    ->helperText('Classe la politique: conduite, securite, discipline, etc.')
                    ->required(),
                TextInput::make('title')
                    ->label('Titre')
                    ->helperText('Titre court et explicite de la politique.')
                    ->required(),
                Textarea::make('content')
                    ->label('Contenu')
                    ->helperText('Texte complet de la politique.')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('target_audience')
                    ->label('Public cible')
                    ->helperText('Exemple: tous, mineurs, responsables, visiteurs.')
                    ->required()
                    ->default('all'),
                TextInput::make('severity_level')
                    ->label('Niveau de severite')
                    ->helperText('Niveau de criticite de 1 (faible) a 5 (eleve).')
                    ->required()
                    ->numeric()
                    ->default(1),
                Toggle::make('is_mandatory')
                    ->label('Obligatoire')
                    ->helperText('Indique si cette politique doit etre obligatoirement acceptee.')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->helperText('Permet d activer ou desactiver cette politique.')
                    ->required(),
                DateTimePicker::make('effective_from')
                    ->label('Effective a partir de')
                    ->helperText("Date et heure de debut d'application."),
                DateTimePicker::make('effective_to')
                    ->label('Effective jusqu a')
                    ->helperText("Date et heure de fin d'application (optionnel)."),
                TextInput::make('created_by')
                    ->label('Cree par (ID utilisateur)')
                    ->helperText('Identifiant de l utilisateur createur.')
                    ->numeric(),
            ]);
    }
}
