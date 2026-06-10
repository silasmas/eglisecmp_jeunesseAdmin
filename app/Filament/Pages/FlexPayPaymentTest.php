<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\FlexPay\FlexPayTestService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use UnitEnum;

/**
 * Interface admin de test FlexPay (mobile, carte, vérification) avec retours bruts.
 */
class FlexPayPaymentTest extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Test FlexPay';

    protected static ?string $title = 'Test paiement FlexPay';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'test-flexpay';

    protected string $view = 'filament.pages.flexpay-payment-test';

    #[Validate('required|in:probe,mobile,card,check')]
    public string $operation = 'mobile';

    #[Validate('nullable|string|max:64')]
    public string $reference = '';

    #[Validate('required|numeric|min:0.01')]
    public string $amount = '1';

    #[Validate('required|string|max:8')]
    public string $currency = 'USD';

    #[Validate('nullable|string|max:30')]
    public string $phone = '243891234567';

    #[Validate('required|string|max:5')]
    public string $flexpayType = '2';

    #[Validate('nullable|string|max:160')]
    public string $description = 'Test FlexPay CMP';

    #[Validate('required|integer|min:5|max:120')]
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
        $this->validate();

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
