<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use App\Support\StoragePath;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Zvizvi\UserFields\Components\UserSelect;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identite')
                    ->schema([
                        FileUpload::make('profile_photo_path')
                            ->label('Photo profil')
                            ->image()
                            ->directory(StoragePath::PROFILES)
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Nom')
                            ->required(),
                        TextInput::make('nom')
                            ->label('Nom (inscription)'),
                        TextInput::make('postnom')
                            ->label('Post-nom'),
                        TextInput::make('prenom')
                            ->label('Prenom'),
                        Select::make('sexe')
                            ->label('Sexe')
                            ->options([
                                'M' => 'Masculin',
                                'F' => 'Feminin',
                            ]),
                        TextInput::make('role_jeunesse')
                            ->label('Role jeunesse')
                            ->default('Ouvrier'),
                        DatePicker::make('date_naissance')
                            ->label('Date de naissance')
                            ->native(false),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),
                        TextInput::make('fonction_metier')
                            ->label('Fonction metier'),
                        Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Ces roles contrôlent les accès Filament et le portail ouvrier.'),
                        Toggle::make('is_active')
                            ->label('Actif')
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Coordonnees (etape 2)')
                    ->schema([
                        TextInput::make('indicatif_telephone')
                            ->label('Indicatif'),
                        TextInput::make('telephone')
                            ->label('Telephone principal'),
                        TextInput::make('telephone_urgence')
                            ->label('Telephone urgence'),
                        TextInput::make('guardian_name')
                            ->label('Nom parent/tuteur'),
                        TextInput::make('guardian_phone')
                            ->label('Telephone parent/tuteur'),
                        TextInput::make('adresse')
                            ->label('Adresse')
                            ->columnSpanFull(),
                        TextInput::make('commune')
                            ->label('Commune'),
                        TextInput::make('ville')
                            ->label('Ville'),
                    ])
                    ->columns(2),
                Section::make('Participation (etape 3)')
                    ->schema([
                        TextInput::make('eglise_assemblee')
                            ->label('Eglise / Assemblee'),
                        TextInput::make('departement_cellule')
                            ->label('Departement / Cellule'),
                        Select::make('hebergement_choice')
                            ->label('Hebergement')
                            ->options([
                                'Oui' => 'Oui',
                                'Non' => 'Non',
                            ]),
                        Textarea::make('observation')
                            ->label('Observation')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Securite')
                    ->schema([
                        TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email verifie le'),
                        DateTimePicker::make('last_login')
                            ->label('Derniere connexion'),
                        UserSelect::make('owner_id')
                            ->label('Responsable parent')
                            ->relationship('owner', 'name')
                            ->searchable(),
                    ])
                    ->columns(2),
            ]);
    }
}
