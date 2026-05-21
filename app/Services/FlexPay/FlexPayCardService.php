<?php

namespace App\Services\FlexPay;

use Illuminate\Support\Facades\Http;

class FlexPayCardService
{
    public function initiateCardPayment(
        float|string $amount,
        string $currency,
        string $reference,
        string $description
    ): array {
        $merchant = config('services.flexpay.merchant');
        $token = config('services.flexpay.token');
        $gateway = config('services.flexpay.gateway_card');
        $appUrl = rtrim((string) config('app.url'), '/');

        if (empty($token) || empty($gateway) || empty($merchant)) {
            return ['rep' => false, 'message' => 'FlexPay (carte) n’est pas configuré.'];
        }

        $baseRedirectUrl = "{$appUrl}/inscription-retraite/paiement-carte/{$reference}/{$amount}/{$currency}";

        $body = [
            'authorization' => 'Bearer '.$token,
            'merchant' => $merchant,
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'callback_url' => "{$appUrl}/api/v1/retreat/inscription/webhooks/flexpay-callback",
            'approve_url' => "{$baseRedirectUrl}/success",
            'cancel_url' => "{$baseRedirectUrl}/cancel",
            'decline_url' => "{$baseRedirectUrl}/decline",
            'home_url' => "{$appUrl}/",
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($gateway, $body);

        $json = $response->json() ?? [];

        if (isset($json['code']) && (string) $json['code'] === '0') {
            return [
                'rep' => true,
                'url' => $json['url'] ?? null,
                'orderNumber' => $json['orderNumber'] ?? null,
                'data' => $json,
            ];
        }

        return [
            'rep' => false,
            'message' => $json['message'] ?? 'Réponse invalide de l’API FlexPay carte.',
        ];
    }
}
