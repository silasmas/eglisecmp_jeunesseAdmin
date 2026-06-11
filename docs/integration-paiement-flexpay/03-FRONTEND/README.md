# Frontend - Intégration

> **Mobile Money multi-opérateurs** : la sélection M-Pesa / Airtel / Orange en interface est **UX uniquement**. L’API FlexPay reçoit toujours `type: "1"`. Voir [`08-MOBILE-MONEY-CORRECTIFS.md`](../08-MOBILE-MONEY-CORRECTIFS.md).

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
- `phoneContainer`, `phone` : pour Mobile Money (12 chiffres, `243…`, sans `+`)
- Si multi-opérateurs : envoyer `provider_code` (ex. `orange`) au backend — **ne pas** l’envoyer comme `type` FlexPay

## Mobile Money — bonnes pratiques UI

| Élément | Recommandation |
|---------|----------------|
| Sélection opérateur | Cartes / boutons avec `data-provider-code="orange"` |
| Payload backend | `{ phone: "243…", provider_code: "orange" }` |
| Validation locale | Regex par opérateur (`msisdn_regex` dans config) |
| Appel FlexPay | Uniquement côté **serveur**, avec `type: "1"` |
- `customCheck7` : checkbox CGU
- `referenceCreate`, `total`, `currency` : champs cachés
- `totalAff` : affichage du total
