<?php

namespace App\Filament\Resources\ChurchEvents\Schemas;

use App\Enums\ChurchEventType;
use App\Enums\EventAccessAuthMode;
use App\Enums\EventAccessOtpChannel;
use App\Support\ChurchEventPublicRegistrationEvaluator;
use App\Support\StoragePath;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use UnitEnum;

class ChurchEventForm
{
    /**
     * @param Schema $schema Schéma Filament
     * @return Schema
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations principales')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required(),
                        Select::make('type')
                            ->label("Type d'événement")
                            ->options(ChurchEventType::options())
                            ->required()
                            ->native(false)
                            ->live(),
                        TextInput::make('location')
                            ->label('Lieu')
                            ->required(),
                        FileUpload::make('affiche')
                            ->label("Affiche de l'événement")
                            ->helperText('Image affichée sur la bannière du formulaire d\'inscription public.')
                            ->directory(StoragePath::EVENTS_AFFICHES)
                            ->image()
                            ->maxSize(8192)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                            ->openable(true)
                            ->downloadable(false)
                            ->columnSpanFull()
                            ->extraFieldWrapperAttributes(['class' => 'cmp-event-affiche-upload']),
                    ])
                    ->columns(2),
                Section::make('Documents billet participant')
                    ->description('Consultables et téléchargeables sur la page billet dès l\'inscription confirmée.')
                    ->schema([
                        FileUpload::make('document_reglement')
                            ->label('Règlement intérieur (PDF)')
                            ->helperText('Rattaché à l\'événement courant — visible sur le billet participant.')
                            ->directory(StoragePath::EVENT_PARTICIPANT_DOCUMENTS.'/reglement')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->downloadable(true)
                            ->openable(true)
                            ->columnSpanFull(),
                        FileUpload::make('document_histoires')
                            ->label('Histoires à apporter (PDF)')
                            ->directory(StoragePath::EVENT_PARTICIPANT_DOCUMENTS.'/histoires')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->downloadable(true)
                            ->openable(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(),
                Section::make('Inscriptions publiques en ligne')
                    ->description('Le formulaire /inscription-retraite s\'ouvre uniquement si toutes les conditions ci-dessous sont remplies (signal vert).')
                    ->schema([
                        Html::make(fn (Get $get): HtmlString => self::registrationStatusIndicator($get))
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Actif (événement courant)')
                            ->helperText('Un seul événement actif à la fois.')
                            ->default(false)
                            ->live()
                            ->required(),
                        Toggle::make('is_publicly_closed')
                            ->label('Fermer l\'accès public (retraite clôturée)')
                            ->helperText('Ferme le portail public. Les ateliers/chambres de cette édition ne s\'affichent plus dans l\'admin opérationnelle.'),
                            ->default(false)
                            ->live(),
                        DateTimePicker::make('public_registration_opens_at')
                            ->label('Début ouverture du formulaire')
                            ->helperText('Vide = ouvert dès que les autres conditions sont OK.')
                            ->seconds(false)
                            ->live(),
                        DateTimePicker::make('public_registration_closes_at')
                            ->label('Fin ouverture du formulaire')
                            ->helperText('Vide = repli sur la date de fin de l\'événement (section Planning).')
                            ->seconds(false)
                            ->after('public_registration_opens_at')
                            ->live(),
                    ])
                    ->columns(2),
                Section::make('Planning et capacite')
                    ->schema([
                        DateTimePicker::make('start_at')
                            ->label('Debut retraite')
                            ->helperText('Ouverture officielle : chambre et atelier visibles sur le billet et l\'e-mail à partir de cette date et heure.'),
                        DateTimePicker::make('end_at')
                            ->label('Fin retraite')
                            ->helperText('Utilisée comme date de fin d\'inscription si « Fin ouverture du formulaire » est vide.')
                            ->live(),
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

    /**
     * Indicateur vert / gris mis à jour en direct dans le formulaire admin.
     *
     * @param Get $get État du formulaire Filament
     * @return HtmlString
     */
    private static function registrationStatusIndicator(Get $get): HtmlString
    {
        return ChurchEventPublicRegistrationEvaluator::renderAdminIndicatorHtml(
            ChurchEventPublicRegistrationEvaluator::evaluateFromAttributes([
                'type' => $get('type'),
                'is_active' => $get('is_active'),
                'is_publicly_closed' => $get('is_publicly_closed'),
                'public_registration_opens_at' => $get('public_registration_opens_at'),
                'public_registration_closes_at' => $get('public_registration_closes_at'),
                'end_at' => $get('end_at'),
            ])
        );
    }

    /**
     * @param mixed $value Valeur enum ou string
     * @param string|null $default Valeur par défaut
     * @return string|null
     */
    private static function normalizeEnumValue(mixed $value, ?string $default = null): ?string
    {
        if ($value instanceof UnitEnum) {
            return $value instanceof \BackedEnum ? (string) $value->value : $value->name;
        }

        return blank($value) ? $default : (string) $value;
    }
}
