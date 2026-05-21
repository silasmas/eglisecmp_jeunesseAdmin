<?php

/**
 * Service FlexPay - Paiement par CARTE BANCAIRE
 *
 * Ce fichier gère l'initiation des paiements par carte via l'API FlexPay.
 * L'utilisateur est redirigé vers la page FlexPay pour saisir ses coordonnées.
 *
 * ADAPTATION : Remplacer "Don" par votre modèle (Commande, Abonnement, etc.)
 */

namespace App\Services;

class FlexPayService
{
    protected $baseUrl;

    protected $merchant;

    protected $token;

    protected $apiUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.flexpay.base_url');
        $this->merchant = config('services.flexpay.merchant');
        $this->token = config('services.flexpay.token');
        $this->apiUrl = env('FLEXPAY_GATEWAY_CARD');
    }

    /**
     * Initie un paiement par carte bancaire.
     *
     * @param  float  $amount  Montant à payer
     * @param  string  $currency  Devise (USD, CDF)
     * @param  string  $reference  Référence unique de la transaction
     * @param  string  $description  Description affichée (ex: "Don", "Achat produit")
     * @param  string  $type  "don" | "produit" | "service" - change les URLs de retour
     * @return array ['rep' => bool, 'url' => string, 'orderNumber' => string, 'message' => string]
     */
    public function initiatePayment($amount, $currency, $reference, $description, $type = 'don')
    {
        $token = env('FLEXPAY_API_TOKEN');
        if (empty($token)) {
            throw new \Exception('Le token FlexPay est vide. Vérifiez votre .env.');
        }

        $baseRedirectUrl = env('APP_URL')."/paid/{$reference}/{$amount}/{$currency}";
        $callbackUrl = env('APP_URL').'/storeTransaction'; // Webhook FlexPay (optionnel)

        $body = [
            'authorization' => 'Bearer '.$token,
            'merchant' => $this->merchant,
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'callback_url' => $callbackUrl,
            'approve_url' => "{$baseRedirectUrl}/success",
            'cancel_url' => "{$baseRedirectUrl}/cancel",
            'decline_url' => "{$baseRedirectUrl}/decline",
            'home_url' => env('APP_URL').'/',
        ];

        $curl = curl_init(env('FLEXPAY_GATEWAY_CARD'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $curlResponse = curl_exec($curl);
        curl_close($curl);

        $json = json_decode($curlResponse, true);

        if (isset($json['code']) && $json['code'] === '0') {
            return [
                'rep' => true,
                'url' => $json['url'],
                'orderNumber' => $json['orderNumber'],
                'data' => $json,
            ];
        }

        return [
            'rep' => false,
            'message' => $json['message'] ?? 'Réponse invalide de l\'API',
            'error' => 'Échec de l\'initiation du paiement',
        ];
    }
}
