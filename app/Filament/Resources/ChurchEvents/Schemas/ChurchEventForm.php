<?php

namespace App\Filament\Resources\ChurchEvents\Schemas;

use App\Enums\EventAccessAuthMode;
use App\Enums\EventAccessOtpChannel;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
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
                            ->required()
                            ->helperText('Pour le formulaire public d\'inscription retraite, saisir exactement : retraite'),
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
                                'Un seul événement actif à la fois. Les inscriptions en ligne restent ouvertes jusqu’à la date de fin (pas la date de début). Sans date de fin, elles restent ouvertes tant que l’événement est actif.'
                            )
                            ->required(),
                        Toggle::make('is_publicly_closed')
                            ->label('Fermer l\'accès public (retraite clôturée)')
                            ->helperText(
                                'À activer après la retraite : ferme inscription, vérification, programme, assistant, billet et QR. Seul le menu « Faire un don » reste actif (sans prise en charge jeunes).'
                            )
                            ->default(false),
                    ])
                    ->columns(2),
                Section::make('Inscriptions publiques en ligne')
                    ->description('Il n\'y a pas de date de début d\'inscription séparée : le portail s\'ouvre dès que les conditions ci-dessous sont remplies.')
                    ->schema([
                        Placeholder::make('registration_rules')
                            ->label('Comment ouvrir les inscriptions ?')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<ol style="margin:0;padding-left:1.25rem;line-height:1.6;">'
                                .'<li><strong>Type</strong> = <code>retraite</code></li>'
                                .'<li><strong>Actif (événement courant)</strong> = oui (un seul à la fois)</li>'
                                .'<li><strong>Date de fin</strong> non dépassée (ou laisser vide pour garder ouvert tant que l\'événement est actif)</li>'
                                .'<li><strong>Fermer l\'accès public</strong> = non (tant que la retraite n\'est pas clôturée)</li>'
                                .'</ol>'
                                .'<p style="margin:0.75rem 0 0;">La <strong>date de début</strong> sert au planning de l\'événement ; elle ne bloque pas l\'ouverture du formulaire.</p>'
                            ))
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(),
                Section::make('Planning et capacite')
                    ->schema([
                        DateTimePicker::make('start_at')
                            ->label('Debut')
                            ->helperText('Début de la retraite (information planning — n\'ouvre pas le formulaire d\'inscription).'),
                        DateTimePicker::make('end_at')
                            ->label('Fin')
                            ->helperText(
                                'Les inscriptions publiques se ferment automatiquement après cette date. Laisser vide pour ne pas fermer automatiquement.'
                            ),
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
