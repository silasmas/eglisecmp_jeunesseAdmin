<?php

return [

    /*
    | URL publique absolue du site (e-mails, billets, liens portail).
    | Sur un hébergement type …/public/jeunesse, incluez le chemin complet :
    | https://jeunesse.eglisecmp.com/public
    */
    'public_base_url' => env('RETRAITE_PUBLIC_BASE_URL', env('APP_URL')),

    /*
    | Adresse de réponse affichée aux participants (évite les réponses vers noreply).
    */
    'mail_reply_to' => env('RETRAITE_MAIL_REPLY_TO', 'Jeunesse@eglisecmp.com'),

    'mail_reply_to_name' => env('RETRAITE_MAIL_REPLY_TO_NAME', 'Jeunesse CMP'),

    /*
    |--------------------------------------------------------------------------
    | Paiements Mobile Money — opérateurs affichés + type API FlexPay
    |--------------------------------------------------------------------------
    | Doc FlexPay v1.4 (paymentService) : le champ API "type" vaut toujours
    |   1 = mobile money (tous opérateurs, routage via le numéro phone)
    |   2 = carte bancaire (sur paymentService ; la carte utilise aussi cardpayment…/v1|v2/pay)
    |
    | Le champ "type" dans flexpay_mobile_providers ci-dessous est un identifiant
    | interne UI (sélection M-Pesa / Airtel / Orange…) — PAS le type FlexPay API.
    | Personnalisez la liste via RETRAITE_FLEXPAY_MOBILE_PROVIDERS dans le .env.
    */
    'flexpay_mobile_money_api_type' => '1',

    'flexpay_card_api_type' => '2',

    'flexpay_mobile_providers' => (function (): array {
        $raw = env('RETRAITE_FLEXPAY_MOBILE_PROVIDERS');
        if ($raw) {
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded) && $decoded !== []) {
                return $decoded;
            }
        }

        /*
         * type : identifiant interne (UI / validation numéro), pas le type API FlexPay.
         * msisdn_regex : numéro normalisé 12 chiffres (243 + 9 chiffres nationaux sans le 0 initial).
         */
        return [
            ['type' => 'mpesa', 'code' => 'mpesa', 'label' => 'M-Pesa', 'msisdn_regex' => '^2438[123][0-9]{7}$'],
            ['type' => 'airtel', 'code' => 'airtel', 'label' => 'Airtel Money', 'msisdn_regex' => '^2439[0-9]{8}$'],
            ['type' => 'orange', 'code' => 'orange', 'label' => 'Orange Money', 'msisdn_regex' => '^2438[459][0-9]{7}$'],
            ['type' => 'afri', 'code' => 'afri', 'label' => 'Afri Money', 'msisdn_regex' => '^2439[0-9]{8}$'],
        ];
    })(),

    /*
    | Si défini : le bouton « Carte bancaire » renvoie vers cette URL au lieu d’initialiser FlexPay carte.
    | Ex. : formulaire physique ou autre portail (query possible : montant, devise, participant_id).
    */
    'card_external_form_url' => env('RETRAITE_CARD_EXTERNAL_FORM_URL'),

    /*
    | Adresses e-mail additionnelles notifiées comme les super_admin (séparées par des virgules).
    | Ex. : Jeunesse@eglisecmp.com pour la boîte du département Jeunesse.
    */
    'admin_notify_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => trim($email),
        explode(',', (string) env('RETRAITE_ADMIN_NOTIFY_EMAILS', 'Jeunesse@eglisecmp.com'))
    ))),

    /*
    | Numéros SMS supplémentaires pour alerter les admins (séparés par des virgules).
    | Les téléphones des comptes super_admin actifs sont aussi utilisés.
    */
    'admin_notify_phones' => array_values(array_filter(array_map(
        static fn (string $phone): string => trim($phone),
        explode(',', (string) env('RETRAITE_ADMIN_NOTIFY_PHONES', ''))
    ))),

    /*
    | E-mail alerté en cas d'échec de paiement d'inscription (FlexPay, annulation, etc.).
    */
    'payment_failure_notify_email' => env(
        'RETRAITE_PAYMENT_FAILURE_NOTIFY_EMAIL',
        'ir-masimango@silasmas.com'
    ),

];
