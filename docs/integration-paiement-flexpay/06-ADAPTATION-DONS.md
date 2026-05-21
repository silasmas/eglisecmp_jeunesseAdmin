# Guide d'adaptation pour le projet DONS

## Checklist d'intégration

### 1. Configuration
- [ ] Ajouter les variables FlexPay dans `.env` (voir `07-EXEMPLE-ENV.md`)
- [ ] Ajouter la config dans `config/services.php`

### 2. Backend
- [ ] Créer la migration `dons` (voir `05-MIGRATIONS.md`)
- [ ] Créer le modèle `App\Models\Don`
- [ ] Copier `FlexPayService.php` dans `app/Services/`
- [ ] Copier les fonctions de `FlexPayMobileHelper.php` dans `app/Helpers/helpers.php`
- [ ] Enregistrer les helpers dans `composer.json` :
  ```json
  "autoload": {
      "files": ["app/Helpers/helpers.php"]
  }
  ```
  Puis : `composer dump-autoload`
- [ ] Copier `DonationPaymentController.php` dans `app/Http/Controllers/`
- [ ] Ajouter les routes (voir `04-ROUTES.md`)

### 3. Frontend
- [ ] Créer la vue `don/formulaire.blade.php` à partir de `formulaire-paiement.blade.php`
- [ ] Créer la vue `don/scripts/paiement.blade.php` à partir de `paiement.blade.php`
- [ ] Adapter les IDs des formulaires si nécessaire (`formDon`, `formPaie`, etc.)
- [ ] Inclure SweetAlert2 (optionnel) : `<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>`
- [ ] S'assurer que la balise `<meta name="csrf-token" content="{{ csrf_token() }}">` est dans le layout

### 4. Page de remerciement
Créer `resources/views/don/merci.blade.php` :

```blade
@extends('layouts.app')
@section('content')
<div class="container">
    <h1>Merci pour votre don !</h1>
    @if(session('message'))
        <p>{{ session('message') }}</p>
    @endif
    @if(session('reference'))
        <p>Référence : {{ session('reference') }}</p>
    @endif
    <a href="/">Retour à l'accueil</a>
</div>
@endsection
```

---

## Adaptations pour d'autres types de projets

### Projet e-commerce (commandes)
- Remplacer `Don` par `Commande`
- Ajouter les champs : `channel`, `description`, `commande_produit` (pivot)
- Adapter `initDon` → `createOrder` avec les produits du panier

### Projet abonnements
- Remplacer `Don` par `Abonnement`
- Ajouter : `plan_id`, `date_fin`, `renouvellement_auto`

### Projet services (comme GroupSynapse)
- Utiliser `service_user` + `Commande`
- Formulaire avec pièce d'identité, premier dépôt, livraison
- Voir le code source de `ServiceUserController::init` et `CartController::createOrderService`

---

## Points d'attention

1. **Référence unique** : Toujours générer une référence unique (DON-XXX, ORD-XXX, etc.)
2. **CSRF** : Inclure le token dans toutes les requêtes POST
3. **Polling Mobile Money** : Vérifier toutes les 5 secondes, max ~14 tentatives
4. **URLs de retour** : FlexPay redirige vers `/paid/{ref}/{amount}/{currency}/success|cancel|decline`
5. **Middleware** : Protéger les routes avec `auth` si les dons sont réservés aux utilisateurs connectés
