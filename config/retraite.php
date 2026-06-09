<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paiements Mobile Money (FlexPay — champ "type" de l’API mobile)
    |--------------------------------------------------------------------------
    | Les codes peuvent différer selon votre contrat FlexPay ; ajustez via .env
    | RETRAITE_FLEXPAY_MOBILE_PROVIDERS en JSON ou modifiez ce tableau par défaut.
    */
    'flexpay_mobile_providers' => (function (): array {
        $raw = env('RETRAITE_FLEXPAY_MOBILE_PROVIDERS');
        if ($raw) {
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded) && $decoded !== []) {
                return $decoded;
            }
        }

        /*
         * msisdn_regex : numéro normalisé 12 chiffres (243 + 9 chiffres nationaux sans le 0 initial).
         * Indicatif international 243 uniquement (pas de + dans la saisie).
         */
        return [
            ['type' => '1', 'code' => 'mpesa', 'label' => 'M-Pesa', 'msisdn_regex' => '^2438[123][0-9]{7}$'],
            ['type' => '2', 'code' => 'airtel', 'label' => 'Airtel Money', 'msisdn_regex' => '^2439[0-9]{8}$'],
            ['type' => '3', 'code' => 'orange', 'label' => 'Orange Money', 'msisdn_regex' => '^2438[459][0-9]{7}$'],
        ];
    })(),

    /*
    | Si défini : le bouton « Carte bancaire » renvoie vers cette URL au lieu d’initialiser FlexPay carte.
    | Ex. : formulaire physique ou autre portail (query possible : montant, devise, participant_id).
    */
    'card_external_form_url' => env('RETRAITE_CARD_EXTERNAL_FORM_URL'),

    /*
    | Numéros SMS supplémentaires pour alerter les admins (séparés par des virgules).
    | Les téléphones des comptes super_admin actifs sont aussi utilisés.
    */
    'admin_notify_phones' => array_values(array_filter(array_map(
        static fn (string $phone): string => trim($phone),
        explode(',', (string) env('RETRAITE_ADMIN_NOTIFY_PHONES', ''))
    ))),

];
