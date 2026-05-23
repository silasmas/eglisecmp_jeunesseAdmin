<?php

namespace App\Filament\Resources\RetreatParticipants\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Zvizvi\UserFields\Components\UserSelect;

class RetreatParticipantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identite et contact')
                    ->schema([
                        TextInput::make('nom')->required(),
                        TextInput::make('prenom')->required(),
                        TextInput::make('age')->numeric()->required(),
                        TextInput::make('email')->label('Email')->email(),
                        TextInput::make('sexe'),
                        TextInput::make('telephone')->tel(),
                        TextInput::make('adresse'),
                        TextInput::make('telephone_urgence')->tel(),
                        TextInput::make('photo'),
                        Textarea::make('observation')->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Rattachement et classification')
                    ->schema([
                        UserSelect::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->searchable(),
                        UserSelect::make('owner_id')
                            ->label('Owner')
                            ->relationship('owner', 'name')
                            ->searchable(),
                        Select::make('atelier_id')
                            ->label('Atelier')
                            ->relationship('atelier', 'numero')
                            ->searchable()
                            ->preload(),
                        Select::make('chambre_id')
                            ->label('Chambre')
                            ->relationship('chambre', 'nom')
                            ->searchable()
                            ->preload(),
                        TextInput::make('role_participant')->required(),
                        TextInput::make('participant_type')->required()->default('internal'),
                        TextInput::make('qr_code'),
                    ])
                    ->columns(2),
                Section::make('Paiement et billet')
                    ->schema([
                        TextInput::make('preuve_paiement')->label('Preuve paiement'),
                        Toggle::make('paiement_valide')->label('Paiement valide')->required(),
                        Toggle::make('billet_envoye')->label('Billet envoye')->required(),
                        DateTimePicker::make('date_billet_envoye')->label('Date envoi billet'),
                        TextInput::make('billet_pdf')->label('Billet PDF'),
                        Toggle::make('billet_envoye_email')->label('Billet envoye par email')->required(),
                        Toggle::make('billet_envoye_whatsapp')->label('Billet envoye par WhatsApp')->required(),
                        Toggle::make('badge_received')
                            ->label('Badge physique remis')
                            ->helperText('Cochez lorsque le badge imprime a ete remis au participant.')
                            ->live()
                            ->afterStateUpdated(function (bool $state, callable $set): void {
                                if ($state) {
                                    $set('badge_received_at', now());
                                }
                            }),
                        DateTimePicker::make('badge_received_at')
                            ->label('Badge remis le')
                            ->visible(fn ($get): bool => (bool) $get('badge_received')),
                    ])
                    ->columns(2),
                Section::make('Presence et regles')
                    ->schema([
                        Toggle::make('present')->label('Present')->required(),
                        DateTimePicker::make('date_presence')->label('Date de presence'),
                        Toggle::make('exit_allowed')->label('Sortie autorisee')->required(),
                        TimePicker::make('curfew_time')->label('Heure limite'),
                        TextInput::make('guardian_name')->label('Nom responsable'),
                        TextInput::make('guardian_phone')->label('Telephone responsable')->tel(),
                    ])
                    ->columns(2),
                Section::make('Inscription OTP et statut')
                    ->schema([
                        TextInput::make('registration_status')
                            ->label('Statut inscription')
                            ->required()
                            ->default('pending'),
                        TextInput::make('registration_otp_code')
                            ->label('Code OTP'),
                        DateTimePicker::make('registration_otp_sent_at')
                            ->label('OTP envoye le'),
                        DateTimePicker::make('registration_otp_expires_at')
                            ->label('OTP expire le'),
                        DateTimePicker::make('registration_otp_verified_at')
                            ->label('OTP verifie le'),
                        TextInput::make('registration_otp_attempts')
                            ->label('Tentatives OTP')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_verified')->label('Verifie')->required(),
                        Toggle::make('is_active')->label('Actif')->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
