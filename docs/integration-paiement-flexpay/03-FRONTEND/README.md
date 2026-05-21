# Frontend - Intégration

## Fichiers

- **formulaire-paiement.blade.php** : Formulaire HTML (montant, nom, email, choix paiement)
- **paiement.blade.php** : Script JavaScript avec les routes Laravel (à inclure dans la page)
- **paiement.js** : Version pure JS (remplacer les URLs manuellement si pas de Blade)

## Utilisation

1. Copier le contenu de `formulaire-paiement.blade.php` dans votre vue (ex: `don/index.blade.php`)
2. Copier `paiement.blade.php` dans `resources/views/don/scripts/` (ou équivalent)
3. Dans votre vue, ajouter : `@include('don.scripts.paiement')`
4. Ou intégrer directement le contenu de `paiement.blade.php` dans un `@section('script')`

## Dépendances

- **SweetAlert2** (optionnel) : pour les alertes stylisées
  ```html
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  ```
- **CSRF** : `<meta name="csrf-token" content="{{ csrf_token() }}">` dans le `<head>`

## IDs requis

Le script attend ces IDs dans le HTML :
- `formDon` : formulaire initial (montant, etc.)
- `btnInitDon` : bouton "Continuer"
- `paiement-section` : bloc choix paiement (masqué au départ)
- `formPaie` : formulaire paiement
- `channel` : select Mobile Money / Carte
- `phoneContainer`, `phone` : pour Mobile Money
- `customCheck7` : checkbox CGU
- `referenceCreate`, `total`, `currency` : champs cachés
- `totalAff` : affichage du total
