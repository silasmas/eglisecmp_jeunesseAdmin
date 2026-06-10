<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\FlexPay\FlexPayTestService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Interface admin de test FlexPay (mobile, carte, vérification) avec formulaire Filament.
 */
class FlexPayPaymentTest extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Test FlexPay';

    protected static ?string $title = 'Test paiement FlexPay';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'test-flexpay';

    public string $operation = 'mobile';

    public string $reference = '';

    public string $amount = '1';

    public string $currency = 'USD';

    public string $phone = '243891234567';

    public string $flexpayType = '2';

    public string $description = 'Test FlexPay CMP';

    public int $timeoutSeconds = 30;

    /** @var array<string, mixed>|null */
    public ?array $lastResult = null;

    /** @var array<string, mixed> */
    public array $configSnapshot = [];

    /**
     * @param array<string, mixed> $parameters Paramètres Filament
     * @return bool Accès réservé au super_admin
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole('super_admin');
    }

    /**
     * Initialise référence et configuration affichée.
     */
    public function mount(): void
    {
        $this->reference = 'TEST-'.now()->format('YmdHis');
        $this->configSnapshot = app(FlexPayTestService::class)->configSnapshot();
    }

    /**
     * @param Schema $schema Schéma Filament
     * @return Schema Contenu de la page de test
     */
    public function content(Schema $schema): Schema
    {
        $components = [
            Section::make('Configuration FlexPay active')
                ->headerActions([
                    Action::make('refreshConfig')
                        ->label('Recharger la config')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->action('refreshConfig'),
                ])
                ->schema([
                    View::make('filament.pages.partials.flexpay-config-snapshot')
                        ->viewData(fn (): array => ['configSnapshot' => $this->configSnapshot]),
                ]),
            Section::make('Lancer un test')
                ->description('Les tests appellent directement FlexPay depuis ce serveur. Utilisez un petit montant et une référence préfixée TEST-.')
                ->schema($this->getTestFormSchema())
                ->columns(2)
                ->footerActions([
                    Action::make('runTest')
                        ->label('Exécuter le test')
                        ->icon('heroicon-o-play')
                        ->action('runTest'),
                    Action::make('regenerateReference')
                        ->label('Nouvelle référence')
                        ->icon('heroicon-o-sparkles')
                        ->color('gray')
                        ->visible(fn (): bool => $this->operation !== 'probe')
                        ->action('regenerateReference'),
                ]),
        ];

        if ($this->lastResult !== null) {
            $components[] = Section::make('Dernier retour FlexPay')
                ->schema([
                    View::make('filament.pages.partials.flexpay-last-result')
                        ->viewData(fn (): array => ['lastResult' => $this->lastResult]),
                ]);
        }

        return $schema->components($components);
    }

    /**
     * @return array<int, Select|TextInput> Champs du formulaire de test
     */
    protected function getTestFormSchema(): array
    {
        return [
            Select::make('operation')
                ->label('Type de test')
                ->options([
                    'probe' => 'Sondage connectivité (GET sur les URLs)',
                    'mobile' => 'Paiement Mobile Money',
                    'card' => 'Paiement carte bancaire',
                    'check' => 'Vérifier une transaction (check)',
                ])
                ->required()
                ->live(),
            TextInput::make('timeoutSeconds')
                ->label('Timeout (secondes)')
                ->numeric()
                ->minValue(5)
                ->maxValue(120)
                ->required(),
            TextInput::make('reference')
                ->label('Référence')
                ->maxLength(64)
                ->visible(fn (): bool => $this->operation !== 'probe')
                ->required(fn (): bool => $this->operation !== 'probe'),
            TextInput::make('amount')
                ->label('Montant')
                ->visible(fn (): bool => in_array($this->operation, ['mobile', 'card'], true))
                ->required(fn (): bool => in_array($this->operation, ['mobile', 'card'], true))
                ->numeric()
                ->minValue(0.01),
            TextInput::make('currency')
                ->label('Devise')
                ->maxLength(8)
                ->visible(fn (): bool => in_array($this->operation, ['mobile', 'card'], true))
                ->required(fn (): bool => in_array($this->operation, ['mobile', 'card'], true)),
            Select::make('flexpayType')
                ->label('Opérateur (type FlexPay)')
                ->options($this->mobileProviderOptions())
                ->visible(fn (): bool => $this->operation === 'mobile')
                ->required(fn (): bool => $this->operation === 'mobile'),
            TextInput::make('phone')
                ->label('Téléphone (12 chiffres, 243…)')
                ->placeholder('243891234567')
                ->maxLength(30)
                ->visible(fn (): bool => $this->operation === 'mobile')
                ->required(fn (): bool => $this->operation === 'mobile'),
            TextInput::make('description')
                ->label('Description')
                ->maxLength(160)
                ->visible(fn (): bool => $this->operation === 'card')
                ->required(fn (): bool => $this->operation === 'card'),
        ];
    }

    /**
     * Rafraîchit l’aperçu de configuration depuis le .env actif.
     */
    public function refreshConfig(): void
    {
        $this->configSnapshot = app(FlexPayTestService::class)->configSnapshot();

        Notification::make()
            ->title('Configuration rechargée')
            ->success()
            ->send();
    }

    /**
     * Génère une nouvelle référence de test.
     */
    public function regenerateReference(): void
    {
        $this->reference = 'TEST-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }

    /**
     * Lance le test FlexPay sélectionné et stocke le retour brut.
     */
    public function runTest(): void
    {
        $this->validate([
            'operation' => ['required', 'in:probe,mobile,card,check'],
            'reference' => ['nullable', 'string', 'max:64'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'max:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'flexpayType' => ['required', 'string', 'max:5'],
            'description' => ['nullable', 'string', 'max:160'],
            'timeoutSeconds' => ['required', 'integer', 'min:5', 'max:120'],
        ]);

        $service = app(FlexPayTestService::class);

        $this->lastResult = match ($this->operation) {
            'probe' => $service->probeGateways($this->timeoutSeconds),
            'mobile' => $service->testMobilePayment(
                $this->reference,
                $this->amount,
                $this->currency,
                preg_replace('/\D+/', '', $this->phone) ?? '',
                $this->flexpayType,
                $this->timeoutSeconds,
            ),
            'card' => $service->testCardPayment(
                $this->reference,
                $this->amount,
                $this->currency,
                $this->description,
                $this->timeoutSeconds,
            ),
            'check' => $service->testCheckTransaction($this->reference, $this->timeoutSeconds),
            default => ['success' => false, 'summary' => 'Opération inconnue.'],
        };

        $success = (bool) ($this->lastResult['success'] ?? false);
        $flexpayAccepted = (bool) ($this->lastResult['flexpay_accepted'] ?? false);

        Notification::make()
            ->title($success ? 'Réponse FlexPay reçue' : 'Échec du test FlexPay')
            ->body((string) ($this->lastResult['summary'] ?? 'Consultez le détail ci-dessous.'))
            ->color($flexpayAccepted || ($this->operation === 'probe' && $success) ? 'success' : ($success ? 'warning' : 'danger'))
            ->send();
    }

    /**
     * Options des opérateurs mobile money pour le formulaire.
     *
     * @return array<string, string>
     */
    public function mobileProviderOptions(): array
    {
        $options = [];

        foreach (config('retraite.flexpay_mobile_providers', []) as $provider) {
            if (! is_array($provider)) {
                continue;
            }

            $type = (string) ($provider['type'] ?? '');
            $label = (string) ($provider['label'] ?? $type);

            if ($type !== '') {
                $options[$type] = $label.' (type '.$type.')';
            }
        }

        if ($options === []) {
            return [
                '1' => 'M-Pesa (type 1)',
                '2' => 'Airtel Money (type 2)',
                '3' => 'Orange Money (type 3)',
            ];
        }

        return $options;
    }
}
