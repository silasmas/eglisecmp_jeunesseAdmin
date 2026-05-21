<?php

namespace App\Filament\Resources\RetreatPayments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Zvizvi\UserFields\Components\UserSelect;

class RetreatPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contexte paiement')
                    ->schema([
                        UserSelect::make('participant_id')
                            ->label('Participant')
                            ->relationship('participant', 'nom')
                            ->searchable()
                            ->required(),
                        Select::make('event_id')
                            ->label('Evenement')
                            ->relationship('event', 'name')
                            ->searchable()
                            ->required(),
                        TextInput::make('reference')
                            ->label('Reference interne')
                            ->required(),
                        Select::make('etat')
                            ->label('Etat')
                            ->options([
                                'init' => 'Init',
                                'en_cours' => 'En cours',
                                'payee' => 'Payee',
                                'annulee' => 'Annulee',
                                'echouee' => 'Echouee',
                                'remboursee' => 'Remboursee',
                            ])
                            ->default('init')
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Montants et canal')
                    ->schema([
                        TextInput::make('amount_expected')
                            ->label('Montant attendu')
                            ->required()
                            ->numeric(),
                        TextInput::make('amount_paid')
                            ->label('Montant recu')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                        TextInput::make('currency')
                            ->label('Devise')
                            ->required()
                            ->default('USD'),
                        TextInput::make('channel')
                            ->label('Canal')
                            ->required(),
                        TextInput::make('phone')
                            ->label('Telephone')
                            ->tel(),
                        DateTimePicker::make('paid_at')
                            ->label('Date paiement'),
                    ])
                    ->columns(2),
                Section::make('Retour provider et acces')
                    ->schema([
                        TextInput::make('provider_reference')
                            ->label('Reference provider'),
                        TextInput::make('provider_status_code')
                            ->label('Code provider'),
                        TextInput::make('provider_message')
                            ->label('Message provider'),
                        Toggle::make('access_granted')
                            ->label('Acces autorise')
                            ->required(),
                        DateTimePicker::make('access_granted_at')
                            ->label("Date d'autorisation"),
                        UserSelect::make('access_granted_by')
                            ->label('Autorise par')
                            ->relationship('accessGrantedBy', 'name')
                            ->searchable()
                            ->preload(),
                        Toggle::make('is_active')
                            ->label('Actif')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
