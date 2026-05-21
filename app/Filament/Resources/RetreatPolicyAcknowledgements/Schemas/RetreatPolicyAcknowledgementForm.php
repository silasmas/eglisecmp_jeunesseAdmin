<?php

namespace App\Filament\Resources\RetreatPolicyAcknowledgements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Zvizvi\UserFields\Components\UserSelect;

class RetreatPolicyAcknowledgementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('policy_id')
                    ->label('Politique')
                    ->helperText('Politique concernee par cet accuse de reception.')
                    ->relationship('policy', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                UserSelect::make('user_id')
                    ->label('Utilisateur')
                    ->helperText('Utilisateur back-office lie a l accuse (optionnel).')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                UserSelect::make('participant_id')
                    ->label('Participant')
                    ->helperText('Participant ayant lu/accepte la politique.')
                    ->relationship('participant', 'nom')
                    ->searchable()
                    ->preload(),
                Toggle::make('has_read')
                    ->label('A lu')
                    ->helperText('Coche si la politique a ete lue.')
                    ->required(),
                Toggle::make('has_accepted')
                    ->label('A accepte')
                    ->helperText('Coche si la politique a ete acceptee.')
                    ->required(),
                DateTimePicker::make('acknowledged_at')
                    ->label('Date d accuse')
                    ->helperText("Date et heure de l'accuse de reception."),
                TextInput::make('signature_type')
                    ->label('Type de signature')
                    ->helperText('Exemple: checkbox, signature_ecran, otp.')
                    ->required()
                    ->default('checkbox'),
                TextInput::make('ip_address')
                    ->label('Adresse IP')
                    ->helperText("Adresse IP du terminal lors de l'accuse."),
                Toggle::make('is_active')
                    ->label('Actif')
                    ->helperText('Permet d activer ou desactiver cet accuse.')
                    ->required(),
            ]);
    }
}
