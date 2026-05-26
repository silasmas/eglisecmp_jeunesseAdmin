<?php

namespace App\Filament\Resources\ChurchEvents\Schemas;

use App\Enums\EventAccessAuthMode;
use App\Enums\EventAccessOtpChannel;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Filament\Forms\FastMediaPicker;
use App\Support\StoragePath;
use UnitEnum;

class ChurchEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations principales')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required(),
                        TextInput::make('type')
                            ->label("Type d'evenement")
                            ->required(),
                        TextInput::make('location')
                            ->label('Lieu')
                            ->required(),
                        FastMediaPicker::make('affiche_id')
                            ->relationship('afficheMedia')
                            ->label("Affiche de l'evenement")
                            ->helperText('Selectionne ou charge une affiche depuis la mediatheque. Si le fichier est absent du stockage, supprimez-le puis choisissez-en un autre.')
                            ->directory(StoragePath::EVENTS_AFFICHES)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                            ->maxSize(8192),
                        Toggle::make('is_active')
                            ->label('Actif (événement courant)')
                            ->helperText(
                                'Un seul événement actif à la fois. Ce réglage n’est plus coupé automatiquement quand la date de début est passée : les inscriptions en ligne se ferment seules si le début est dépassé (contrôle API), sans désactiver la fiche ici.'
                            )
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Planning et capacite')
                    ->schema([
                        DateTimePicker::make('start_at')
                            ->label('Debut'),
                        DateTimePicker::make('end_at')
                            ->label('Fin'),
                        TextInput::make('capacity')
                            ->label('Capacite')
                            ->numeric(),
                    ])
                    ->columns(2),
                Section::make('Tarification et acces')
                    ->schema([
                        TextInput::make('price_to_pay')
                            ->label('Montant a payer')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                        TextInput::make('currency')
                            ->label('Devise')
                            ->required()
                            ->default('USD'),
                        Select::make('access_auth_mode')
                            ->label("Mode d'authentification")
                            ->options([
                                EventAccessAuthMode::Password->value => EventAccessAuthMode::Password->label(),
                                EventAccessAuthMode::Otp->value => EventAccessAuthMode::Otp->label(),
                            ])
                            ->default('password')
                            ->dehydrateStateUsing(fn (mixed $state): string => self::normalizeEnumValue(
                                $state,
                                EventAccessAuthMode::Password->value,
                            ))
                            ->required(),
                        Select::make('access_otp_channel')
                            ->label('Canal OTP')
                            ->options([
                                EventAccessOtpChannel::Sms->value => EventAccessOtpChannel::Sms->label(),
                                EventAccessOtpChannel::Email->value => EventAccessOtpChannel::Email->label(),
                            ])
                            ->nullable()
                            ->dehydrateStateUsing(fn (mixed $state): ?string => self::normalizeEnumValue($state))
                            ->helperText('Canal OTP portail et envoi du billet après paiement (SMS ou e-mail).'),
                    ])
                    ->columns(2),
            ]);
    }

    private static function normalizeEnumValue(mixed $value, ?string $default = null): ?string
    {
        if ($value instanceof UnitEnum) {
            return $value instanceof \BackedEnum ? (string) $value->value : $value->name;
        }

        return blank($value) ? $default : (string) $value;
    }
}
