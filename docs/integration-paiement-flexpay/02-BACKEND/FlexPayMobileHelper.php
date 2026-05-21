<?php

/**
 * Helper FlexPay - Paiement MOBILE MONEY
 *
 * Envoie une requête à l'API FlexPay pour déclencher un paiement Mobile Money.
 * L'utilisateur reçoit une notification sur son téléphone pour valider.
 *
 * ADAPTATION : Remplacer "Don" par votre modèle (Commande, Abonnement, etc.)
 */

use App\Models\Don;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

if (! function_exists('initRequeteFlexPayMobile')) {
    /**
     * @param  array  $data  [merchant, type, phone, reference, amount, currency, callbackUrl]
     * @param  object  $order  Modèle avec update() et champs provider_reference, etat
     * @return array|JsonResponse
     */
    function initRequeteFlexPayMobile(array $data, $order)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.env('FLEXPAY_API_TOKEN'),
        ])->post(env('FLEXPAY_GATEWAY_MOBILE'), $data);

        $responseBody = $response->json();

        if (isset($responseBody['code']) && $responseBody['code'] == '0') {
            $order->update([
                'provider_reference' => $responseBody['orderNumber'],
                'etat' => 'En cours',
            ]);

            return [
                'reponse' => true,
                'message' => 'Paiement en attente',
                'type' => 'mobile',
                'reference' => $order->reference ?? $data['reference'],
                'orderNumber' => $responseBody['orderNumber'],
            ];
        }

        return [
            'reponse' => false,
            'message' => $responseBody['message'] ?? 'Échec de la transaction',
        ];
    }
}

if (! function_exists('generateUniqueReference')) {
    /**
     * Génère une référence unique pour une transaction.
     * ADAPTATION : Remplacer Don::class par votre modèle.
     */
    function generateUniqueReference($modelClass = null)
    {
        $modelClass = $modelClass ?? Don::class;
        do {
            $reference = 'DON-'.strtoupper(Str::random(10));
        } while ($modelClass::where('reference', $reference)->exists());

        return $reference;
    }
}
