<?php

namespace App\Services\FlexPay;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlexPayMobileService
{
    public function initiateMobilePayment(
        string $reference,
        float|string $amount,
        string $currency,
        string $phone,
        ?string $type = null
    ): array {
        $apiType = $type ?? (string) config('retraite.flexpay_mobile_money_api_type', '1');
        $token = config('services.flexpay.token');
        $url = config('services.flexpay.gateway_mobile');
        $merchant = config('services.flexpay.merchant');

        if (empty($token) || empty($url) || empty($merchant)) {
            return [
                'reponse' => false,
                'message' => 'Le paiement mobile n’est pas configuré côté serveur (identifiants manquants). Contactez l’organisation.',
                'raw' => null,
            ];
        }

        $body = [
            'merchant' => $merchant,
            'type' => $apiType,
            'phone' => $phone,
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'callbackUrl' => url('/api/v1/retreat/inscription/webhooks/flexpay-callback'),
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ])->post($url, $body);

        $payload = $response->json() ?? [];

        if (! $response->successful()) {
            Log::warning('FlexPay mobile HTTP non 2xx', [
                'status' => $response->status(),
                'body_snippet' => mb_substr($response->body(), 0, 500),
                'reference' => $reference,
            ]);

            return [
                'reponse' => false,
                'message' => 'Le service du réseau de paiement n’a pas répondu correctement (connexion ou serveur distant). Réessayez dans quelques instants.',
                'raw' => ['http_status' => $response->status(), 'payload' => $payload],
            ];
        }

        Log::channel('daily')->debug('FlexPay mobile réponse brute', ['payload' => $payload]);

        if (isset($payload['code']) && (string) $payload['code'] === '0') {
            return [
                'reponse' => true,
                'message' => $this->sanitizeUserFacingPaymentMessage((string) ($payload['message'] ?? 'Demande envoyée sur votre téléphone.')),
                'type' => 'mobile',
                'reference' => $reference,
                'orderNumber' => $payload['orderNumber'] ?? null,
                'raw' => $payload,
            ];
        }

        /** @var array<string, mixed> $payload */

        return [
            'reponse' => false,
            'message' => $this->sanitizeUserFacingPaymentMessage(
                (string) ($payload['message'] ?? ''),
                fallback: $this->messageForPaymentRefusalCode($payload)
            ),
            'raw' => $payload,
        ];
    }

    /**
     * Ne pas exposer le nom commercial du passerelle aux participants.
     */
    protected function sanitizeUserFacingPaymentMessage(string $message, ?string $fallback = null): string
    {
        $t = trim((string) preg_replace('/FlexPay\s*/iu', '', $message));
        $t = trim($t);

        if ($t === '' || preg_match('/échec\s+de\s+l[’\']?initiation/iu', $message)) {
            return $fallback ?? 'L’initialisation du paiement par mobile money a été refusée ou n’a pu aboutir. Vérifiez le numéro, le réseau choisi puis réessayez.';
        }

        return $t !== '' ? $t : ($fallback ?? 'Impossible de lancer ce paiement mobile pour le moment.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function messageForPaymentRefusalCode(array $payload): string
    {
        $code = isset($payload['code']) ? (string) $payload['code'] : '';

        return match ($code) {
            '1' => 'La demande a été rejetée par l’opérateur (numéro, solde ou type de compte). Vérifiez les informations et réessayez.',
            '2' => 'Le montant ou la devise n’est pas accepté par l’opérateur. Contactez l’organisation.',
            default => 'L’opérateur mobile n’a pas accepté la demande de paiement. Vérifiez votre numéro et le réseau sélectionné.',
        };
    }

    public function checkTransaction(string $reference): array
    {
        $token = config('services.flexpay.token');
        $base = rtrim((string) config('services.flexpay.gateway_check'), '/');
        $url = $base.'/'.urlencode($reference);

        if (empty($token)) {
            return ['ok' => false, 'error' => 'Token FlexPay manquant'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($url);

        $json = $response->json() ?? [];

        if (! $response->successful()) {
            Log::warning('FlexPay check transaction HTTP non 2xx', [
                'status' => $response->status(),
                'reference' => $reference,
                'body_snippet' => mb_substr($response->body(), 0, 500),
            ]);

            return [
                'ok' => false,
                'error' => 'Le service de vérification opérateur ne répond pas correctement pour le moment.',
                'http_status' => $response->status(),
                'payload' => $json,
            ];
        }

        return ['ok' => true, 'payload' => $json];
    }
}
