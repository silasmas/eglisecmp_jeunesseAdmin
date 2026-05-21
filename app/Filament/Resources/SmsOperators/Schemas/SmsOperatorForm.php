<?php

namespace App\Filament\Resources\SmsOperators\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SmsOperatorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Configuration opérateur')
                ->schema([
                    TextInput::make('name')
                        ->label('Nom')
                        ->default('Keccel')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('provider')
                        ->label('Fournisseur')
                        ->default('keccel')
                        ->required()
                        ->maxLength(40),
                    TextInput::make('send_url')
                        ->label('URL d’envoi')
                        ->default(fn (): ?string => config('services.sms.url'))
                        ->required()
                        ->url(),
                    TextInput::make('balance_url')
                        ->label('URL de consultation du solde')
                        ->default(fn (): ?string => config('services.sms.balance_url'))
                        ->helperText('Optionnel : renseignez l’endpoint Keccel de solde si disponible dans votre compte/API.')
                        ->url(),
                    TextInput::make('delivery_url')
                        ->label('URL de vérification livraison')
                        ->default(fn (): ?string => config('services.sms.delivery_url'))
                        ->helperText('Endpoint Keccel delivery.asp utilisé avec from, token et messageid.')
                        ->url(),
                    TextInput::make('token')
                        ->label('Token API')
                        ->default(fn (): ?string => config('services.sms.token'))
                        ->required(),
                    TextInput::make('sender')
                        ->label('Expéditeur')
                        ->default(fn (): string => (string) config('services.sms.from', 'CMP'))
                        ->required()
                        ->maxLength(50),
                    Select::make('send_method')
                        ->label('Méthode d’envoi')
                        ->options([
                            'POST' => 'POST',
                            'GET' => 'GET',
                        ])
                        ->default('POST')
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Opérateur actif')
                        ->helperText('Un seul opérateur actif est utilisé pour les envois publics.')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }
}
