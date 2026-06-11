# Backend - Intégration

> **Mobile Money** : lire [`08-MOBILE-MONEY-CORRECTIFS.md`](../08-MOBILE-MONEY-CORRECTIFS.md) avant toute intégration multi-opérateurs.

## Fichiers à copier

| Fichier | Destination |
|---------|-------------|
| FlexPayService.php | `app/Services/FlexPayService.php` |
| FlexPayMobileService.example.php | Modèle pour `app/Services/FlexPay/FlexPayMobileService.php` |
| DonationPaymentController.php | `app/Http/Controllers/DonationPaymentController.php` |

## Helpers à ajouter

Copier le contenu de `FlexPayMobileHelper.php` dans votre `app/Helpers/helpers.php`.

Si vous n'avez pas de fichier helpers :

1. Créer `app/Helpers/helpers.php`
2. Y coller le contenu de `FlexPayMobileHelper.php`
3. Dans `composer.json`, section `autoload` :
   ```json
   "autoload": {
       "files": ["app/Helpers/helpers.php"]
   }
   ```
4. Exécuter : `composer dump-autoload`

## Service Mobile Money (projets avancés)

Pour une intégration avec **sélection d’opérateur en UI** (M-Pesa, Airtel, Orange…), s’inspirer de :

- `app/Services/FlexPay/FlexPayMobileService.php` (projet retraite)
- `config/retraite.php` ou `config/flexpay.php` — constante `flexpay_mobile_money_api_type` = `'1'`

Règle : le backend reçoit un **`provider_code`** (ex. `orange`) pour validation ; l’appel FlexPay envoie **`type: "1"`**.

## Validation Laravel

Si le front envoie un code opérateur texte :

```php
'provider_code' => ['required', 'string', 'max:32'], // pas max:5 — "airtel" et "orange" font 6 caractères
```

## Modèle Don

Créer `app/Models/Don.php` (voir `05-MIGRATIONS.md` pour le schéma).

## Injection FlexPayService

Le `DonationPaymentController` reçoit `FlexPayService` par injection. Laravel le résout automatiquement si le service est dans `app/Services/`.
