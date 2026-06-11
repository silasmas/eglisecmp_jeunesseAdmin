# Configuration FlexPay

> **Mobile Money** : lire impérativement [`08-MOBILE-MONEY-CORRECTIFS.md`](08-MOBILE-MONEY-CORRECTIFS.md) — le champ API `type` vaut **`"1"`** pour tous les opérateurs (M-Pesa, Airtel, Orange…).

---

## Variables d'environnement (.env)

```env
# FlexPay — identifiants marchand
FLEXPAY_API_TOKEN=votre_token_jwt_flexpay
FLEXPAY_MARCHAND=votre_code_marchand

# Passerelles (confirmer les URLs avec FlexPay selon votre contrat)
FLEXPAY_GATEWAY_MOBILE=https://backend.flexpay.cd/api/rest/v1/paymentService
FLEXPAY_GATEWAY_CARD=https://cardpayment.flexpay.cd/v1.1/pay
FLEXPAY_GATEWAY_CHECK=https://backend.flexpay.cd/api/rest/v1/check

# Optionnel — réseaux Mobile Money affichés dans l’UI (identifiants internes, pas types API)
# JSON entre guillemets simples obligatoires
FLEXPAY_MOBILE_PROVIDERS='[{"type":"mpesa","code":"mpesa","label":"M-Pesa","msisdn_regex":"^2438[123][0-9]{7}$"},{"type":"airtel","code":"airtel","label":"Airtel Money","msisdn_regex":"^2439[0-9]{8}$"},{"type":"orange","code":"orange","label":"Orange Money","msisdn_regex":"^2438[459][0-9]{7}$"},{"type":"afri","code":"afri","label":"Afri Money","msisdn_regex":"^2439[0-9]{8}$"}]'
```

> Anciennes docs mentionnaient `/api/rest/v1/mobile` — certains marchands utilisent **`/paymentService`** (doc FlexPay v1.4). Utilisez l’URL fournie pour votre compte.

---

## config/services.php

```php
'flexpay' => [
    'merchant' => env('FLEXPAY_MARCHAND'),
    'token' => env('FLEXPAY_API_TOKEN'),
    'gateway_mobile' => env('FLEXPAY_GATEWAY_MOBILE', 'https://backend.flexpay.cd/api/rest/v1/paymentService'),
    'gateway_card' => env('FLEXPAY_GATEWAY_CARD', 'https://cardpayment.flexpay.cd/v1.1/pay'),
    'gateway_check' => env('FLEXPAY_GATEWAY_CHECK', 'https://backend.flexpay.cd/api/rest/v1/check'),
],
```

---

## Types API FlexPay (doc v1.4 — paymentService)

| `type` | Canal |
|--------|--------|
| `"1"` | Mobile Money (**unique valeur** pour M-Pesa, Airtel, Orange, Afri…) |
| `"2"` | Carte bancaire (si routé via paymentService) |

L’opérateur mobile est choisi par FlexPay à partir du **`phone`** (12 chiffres, `243…`).

---

## config/flexpay.php (recommandé pour nouveaux projets)

Exemple minimal à créer :

```php
<?php

return [
    'flexpay_mobile_money_api_type' => '1',
    'flexpay_card_api_type' => '2',

    'flexpay_mobile_providers' => (function (): array {
        $raw = env('FLEXPAY_MOBILE_PROVIDERS');
        if ($raw) {
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded) && $decoded !== []) {
                return $decoded;
            }
        }

        return [
            ['type' => 'mpesa', 'code' => 'mpesa', 'label' => 'M-Pesa', 'msisdn_regex' => '^2438[123][0-9]{7}$'],
            ['type' => 'airtel', 'code' => 'airtel', 'label' => 'Airtel Money', 'msisdn_regex' => '^2439[0-9]{8}$'],
            ['type' => 'orange', 'code' => 'orange', 'label' => 'Orange Money', 'msisdn_regex' => '^2438[459][0-9]{7}$'],
            ['type' => 'afri', 'code' => 'afri', 'label' => 'Afri Money', 'msisdn_regex' => '^2439[0-9]{8}$'],
        ];
    })(),
];
```

Le champ `type` dans chaque opérateur = **identifiant UI**, pas le `type` envoyé à FlexPay.

---

## Où obtenir les credentials ?

1. Créer un compte sur [FlexPay](https://flexpay.cd)
2. Obtenir le **token JWT** (API)
3. Obtenir le **code marchand**
4. Demander les **URLs exactes** des gateways et les codes `type` documentés pour votre contrat
