# Configuration FlexPay

## Variables d'environnement (.env)

```env
# FlexPay - Paiement
FLEXPAY_API_TOKEN=votre_token_jwt_flexpay
FLEXPAY_MARCHAND=votre_code_marchand
FLEXPAY_GATEWAY_MOBILE=https://backend.flexpay.cd/api/rest/v1/mobile
FLEXPAY_GATEWAY_CARD=https://backend.flexpay.cd/api/rest/v1/card
FLEXPAY_GATEWAY_CHECK=https://backend.flexpay.cd/api/rest/v1/check
```

## config/services.php

Ajouter dans le tableau `return` :

```php
'flexpay' => [
    'merchant' => env('FLEXPAY_MARCHAND'),
    'token'    => env('FLEXPAY_API_TOKEN'),
    'base_url' => env('FLEXPAY_GATEWAY_MOBILE'),
],
```

## Où obtenir les credentials ?

1. Créer un compte sur [FlexPay](https://flexpay.cd)
2. Obtenir le **token JWT** (API)
3. Obtenir le **code marchand**
4. Les URLs des gateways sont généralement fournies par FlexPay
