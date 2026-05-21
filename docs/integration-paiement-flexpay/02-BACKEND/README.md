# Backend - Intégration

## Fichiers à copier

| Fichier | Destination |
|---------|-------------|
| FlexPayService.php | `app/Services/FlexPayService.php` |
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

## Modèle Don

Créer `app/Models/Don.php` (voir `05-MIGRATIONS.md` pour le schéma).

## Injection FlexPayService

Le `DonationPaymentController` reçoit `FlexPayService` par injection. Laravel le résout automatiquement si le service est dans `app/Services/`.
