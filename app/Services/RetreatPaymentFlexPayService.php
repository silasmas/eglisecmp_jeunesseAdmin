<?php

namespace App\Services;

use App\Models\RetreatPayment;
use App\Services\FlexPay\FlexPayCardService;
use App\Services\FlexPay\FlexPayMobileService;
use App\Services\FlexPay\FlexPayTransactionStatusReader;
use Illuminate\Support\Facades\DB;

/**
 * Vérification et relance FlexPay pour les paiements d'inscription retraite (admin).
 */
class RetreatPaymentFlexPayService
{
    /** @var list<string> */
    protected const FINAL_STATES = ['payee', 'remboursee'];

    /** @var list<string> */
    protected const RELAUNCH_STATES = ['init', 'echouee', 'annulee'];

    /** @var list<string> */
    protected const ELECTRONIC_CHANNELS = ['mobile_money', 'card'];

    public function __construct(
        protected FlexPayMobileService $flexPayMobile,
        protected FlexPayCardService $flexPayCard,
        protected FlexPayTransactionStatusReader $flexPayStatusReader,
        protected RetreatInscriptionPaymentCompletionService $paymentCompletion,
    ) {}

    /**
     * Indique si une vérification FlexPay (polling) est pertinente pour ce paiement.
     *
     * @param RetreatPayment $payment Paiement inscription
     * @return bool
     */
    public function canRecheck(RetreatPayment $payment): bool
    {
        if (in_array($payment->etat, self::FINAL_STATES, true)) {
            return false;
        }

        if (! in_array($payment->channel, self::ELECTRONIC_CHANNELS, true)) {
            return false;
        }

        if ($payment->channel === 'cash') {
            return false;
        }

        return filled($payment->provider_reference) || $payment->etat === 'en_cours';
    }

    /**
     * Indique si une nouvelle demande FlexPay peut être lancée depuis l'admin.
     *
     * @param RetreatPayment $payment Paiement inscription
     * @return bool
     */
    public function canRelaunch(RetreatPayment $payment): bool
    {
        if (in_array($payment->etat, self::FINAL_STATES, true)) {
            return false;
        }

        if (! in_array($payment->channel, self::ELECTRONIC_CHANNELS, true)) {
            return false;
        }

        return in_array($payment->etat, self::RELAUNCH_STATES, true);
    }

    /**
     * Interroge FlexPay et confirme le paiement si l'opérateur a encaissé.
     *
     * @param RetreatPayment $payment Paiement à vérifier
     * @return array{confirmed: bool, message: string, statut_code: int|null, payment: RetreatPayment}
     */
    public function recheckPayment(RetreatPayment $payment): array
    {
        if (! $this->canRecheck($payment)) {
            throw new \RuntimeException('Ce paiement ne peut pas être vérifié via FlexPay.');
        }

        if ($payment->etat === 'payee' || (bool) $payment->participant?->paiement_valide) {
            return [
                'confirmed' => true,
                'message' => 'Ce paiement est déjà confirmé.',
                'statut_code' => 0,
                'payment' => $payment->fresh(['participant', 'event']),
            ];
        }

        $lookupReference = filled($payment->provider_reference)
            ? (string) $payment->provider_reference
            : $payment->reference;

        $check = $this->flexPayMobile->checkTransaction($lookupReference);
        $this->logTransaction($payment, 'admin_recheck', [
            'reference' => $payment->reference,
            'provider_reference' => $lookupReference,
        ], $check);

        if (! ($check['ok'] ?? false)) {
            throw new \RuntimeException($check['error'] ?? 'Erreur lors de la vérification FlexPay.');
        }

        $payload = is_array($check['payload'] ?? null) ? $check['payload'] : [];
        $merged = $this->flexPayStatusReader->mergePayload($payload);
        $statusRaw = $this->flexPayStatusReader->extractStatus($merged);

        if ($statusRaw === null || $statusRaw === '' || ! is_numeric($statusRaw)) {
            return [
                'confirmed' => false,
                'message' => data_get($merged, 'message', 'Statut indisponible — le paiement n’est pas confirmé.'),
                'statut_code' => null,
                'payment' => $payment->fresh(['participant', 'event']),
            ];
        }

        $status = (int) $statusRaw;

        if ($status === 0) {
            $this->paymentCompletion->markElectronicPaid($payment->fresh(), 'FlexPay confirmé via vérification admin.');

            return [
                'confirmed' => true,
                'message' => 'Paiement confirmé par l’opérateur.',
                'statut_code' => 0,
                'payment' => $payment->fresh(['participant', 'event']),
            ];
        }

        if ($status === 1) {
            $payment->update([
                'etat' => 'annulee',
                'provider_message' => 'Transaction annulée côté opérateur (vérification admin).',
            ]);

            return [
                'confirmed' => false,
                'message' => 'Transaction annulée côté opérateur.',
                'statut_code' => 1,
                'payment' => $payment->fresh(['participant', 'event']),
            ];
        }

        if ($status === 2) {
            $payment->update(['etat' => 'en_cours']);
        }

        return [
            'confirmed' => false,
            'message' => $status === 2
                ? 'Paiement toujours en attente chez l’opérateur.'
                : data_get($merged, 'message', 'Statut inconnu.'),
            'statut_code' => $status,
            'payment' => $payment->fresh(['participant', 'event']),
        ];
    }

    /**
     * Relance une demande FlexPay (mobile ou carte) pour un paiement non abouti.
     *
     * @param RetreatPayment $payment Paiement à relancer
     * @param string|null $phone Numéro MSISDN 243… (requis pour mobile si absent du dossier)
     * @return array{success: bool, message: string, redirect_url: string|null, payment: RetreatPayment}
     */
    public function relaunchPayment(RetreatPayment $payment, ?string $phone = null): array
    {
        if (! $this->canRelaunch($payment)) {
            throw new \RuntimeException('Ce paiement ne peut pas être relancé via FlexPay.');
        }

        return match ($payment->channel) {
            'mobile_money' => $this->relaunchMobilePayment($payment, $phone),
            'card' => $this->relaunchCardPayment($payment),
            default => throw new \RuntimeException('Canal de paiement non pris en charge pour la relance FlexPay.'),
        };
    }

    /**
     * Relance un paiement Mobile Money.
     *
     * @param RetreatPayment $payment Paiement inscription
     * @param string|null $phone Numéro 243…
     * @return array{success: bool, message: string, redirect_url: string|null, payment: RetreatPayment}
     */
    protected function relaunchMobilePayment(RetreatPayment $payment, ?string $phone): array
    {
        $normalized = $this->normalizePhone($phone ?? (string) $payment->phone);

        if ($normalized === null) {
            throw new \RuntimeException('Numéro mobile requis (12 chiffres commençant par 243).');
        }

        $result = $this->flexPayMobile->initiateMobilePayment(
            $payment->reference,
            $payment->amount_expected,
            (string) $payment->currency,
            $normalized,
            (string) config('retraite.flexpay_mobile_money_api_type', '1'),
        );

        $this->logTransaction($payment, 'admin_mobile_relaunch', [
            'phone' => $normalized,
            'reference' => $payment->reference,
        ], $result);

        if (! ($result['reponse'] ?? false)) {
            throw new \RuntimeException($result['message'] ?? 'Impossible de relancer le paiement mobile.');
        }

        $payment->update([
            'phone' => $normalized,
            'provider_reference' => $result['orderNumber'] ?? $payment->provider_reference,
            'provider_message' => $result['message'] ?? null,
            'etat' => 'en_cours',
            'channel' => 'mobile_money',
        ]);

        return [
            'success' => true,
            'message' => $result['message'] ?? 'Demande Mobile Money renvoyée sur le téléphone.',
            'redirect_url' => null,
            'payment' => $payment->fresh(['participant', 'event']),
        ];
    }

    /**
     * Relance un paiement carte (redirection FlexPay).
     *
     * @param RetreatPayment $payment Paiement inscription
     * @return array{success: bool, message: string, redirect_url: string|null, payment: RetreatPayment}
     */
    protected function relaunchCardPayment(RetreatPayment $payment): array
    {
        $description = 'Retraite — '.($payment->event?->name ?? 'inscription');

        $result = $this->flexPayCard->initiateCardPayment(
            $payment->amount_expected,
            (string) $payment->currency,
            $payment->reference,
            $description,
        );

        $this->logTransaction($payment, 'admin_card_relaunch', [
            'reference' => $payment->reference,
        ], $result);

        if (! ($result['rep'] ?? false)) {
            throw new \RuntimeException($result['message'] ?? 'Impossible de relancer le paiement par carte.');
        }

        $payment->update([
            'provider_reference' => $result['orderNumber'] ?? $payment->provider_reference,
            'provider_message' => null,
            'etat' => 'en_cours',
            'channel' => 'card',
        ]);

        return [
            'success' => true,
            'message' => 'Page de paiement carte prête. Ouvrez le lien FlexPay pour finaliser.',
            'redirect_url' => $result['url'] ?? null,
            'payment' => $payment->fresh(['participant', 'event']),
        ];
    }

    /**
     * Normalise un numéro congolais en MSISDN 243XXXXXXXXX (12 chiffres).
     *
     * @param string $phone Numéro saisi
     * @return string|null MSISDN valide ou null
     */
    protected function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '243'.substr($digits, 1);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '243')) {
            return $digits;
        }

        return null;
    }

    /**
     * Journalise une interaction FlexPay admin dans retreat_payment_transactions.
     *
     * @param RetreatPayment $payment Paiement concerné
     * @param string $type Type d'opération
     * @param array<string, mixed>|null $requestPayload Requête
     * @param mixed $responsePayload Réponse
     * @return void
     */
    protected function logTransaction(RetreatPayment $payment, string $type, ?array $requestPayload, mixed $responsePayload): void
    {
        try {
            DB::table('retreat_payment_transactions')->insert([
                'payment_id' => $payment->id,
                'transaction_type' => $type,
                'provider_reference' => $payment->provider_reference,
                'request_payload' => $requestPayload ? json_encode($requestPayload) : null,
                'response_payload' => is_array($responsePayload)
                    ? json_encode($responsePayload)
                    : json_encode(['value' => $responsePayload]),
                'message' => null,
                'processed_at' => now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
