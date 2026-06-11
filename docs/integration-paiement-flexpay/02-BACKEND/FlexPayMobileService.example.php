<?php

/**
 * Exemple de service Mobile Money FlexPay — à adapter dans app/Services/FlexPay/FlexPayMobileService.php
 *
 * Doc interne : docs/integration-paiement-flexpay/08-MOBILE-MONEY-CORRECTIFS.md
 *
 * Règle : le paramètre $apiType passé à FlexPay vaut TOUJOURS "1" pour Mobile Money.
 */

namespace App\Services\FlexPay;

use Illuminate\Support\Facades\Http;

class FlexPayMobileService
{
    /**
     * Initie un paiement Mobile Money via paymentService.
     *
     * @param string $reference Référence marchande unique
     * @param float|string $amount Montant
     * @param string $currency Devise (CDF, USD…)
     * @param string $phone Numéro 12 chiffres (243…)
     * @param string|null $apiType Type API FlexPay — laisser null pour utiliser "1"
     * @return array{reponse: bool, message: string, orderNumber?: string, raw?: mixed}
     */
    public function initiateMobilePayment(
        string $reference,
        float|string $amount,
        string $currency,
        string $phone,
        ?string $apiType = null
    ): array {
        $token = config('services.flexpay.token');
        $url = config('services.flexpay.gateway_mobile');
        $merchant = config('services.flexpay.merchant');
        $type = $apiType ?? (string) config('flexpay.flexpay_mobile_money_api_type', '1');

        if (empty($token) || empty($url) || empty($merchant)) {
            return [
                'reponse' => false,
                'message' => 'Paiement mobile non configuré (identifiants manquants).',
                'raw' => null,
            ];
        }

        $body = [
            'merchant' => $merchant,
            'type' => $type,
            'phone' => $phone,
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'callbackUrl' => url('/api/payment/flexpay-callback'),
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ])->post($url, $body);

        $payload = $response->json() ?? [];

        if (isset($payload['code']) && (string) $payload['code'] === '0') {
            return [
                'reponse' => true,
                'message' => (string) ($payload['message'] ?? 'Demande envoyée sur votre téléphone.'),
                'orderNumber' => $payload['orderNumber'] ?? null,
                'raw' => $payload,
            ];
        }

        return [
            'reponse' => false,
            'message' => (string) ($payload['message'] ?? 'Paiement mobile refusé.'),
            'raw' => $payload,
        ];
    }
}
