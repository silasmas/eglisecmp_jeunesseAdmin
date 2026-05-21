# Intégration Paiement FlexPay

Ce dossier contient tout le code et la documentation nécessaires pour intégrer le paiement FlexPay (Mobile Money + Carte bancaire) dans un projet Laravel.

**Utilisable pour :** Dons, achats, abonnements, services, etc.

---

## 📁 Structure du dossier

```
integration-paiement-flexpay/
├── README.md                    # Ce fichier
├── 01-CONFIGURATION.md          # Variables d'environnement et config
├── 02-BACKEND/
│   ├── README.md                # Instructions copie backend
│   ├── FlexPayService.php       # Service API FlexPay (carte bancaire)
│   ├── FlexPayMobileHelper.php  # Helper Mobile Money (à intégrer dans helpers.php)
│   └── DonationPaymentController.php  # Contrôleur adapté pour les dons
├── 03-FRONTEND/
│   ├── README.md                # Instructions frontend
│   ├── formulaire-paiement.blade.php  # Formulaire HTML
│   ├── paiement.blade.php       # Script JS avec routes Laravel
│   └── paiement.js              # Version JS pure (sans Blade)
├── 04-ROUTES.md                 # Routes à ajouter
├── 05-MIGRATIONS.md             # Migrations base de données
├── 06-ADAPTATION-DONS.md        # Guide spécifique projet dons
└── 07-EXEMPLE-ENV.md            # Exemple .env
```

---

## 🚀 Démarrage rapide

1. **Configuration** : Copier les variables dans `.env` (voir `07-EXEMPLE-ENV.md`)
2. **Backend** : Copier les fichiers de `02-BACKEND/` dans votre projet
3. **Frontend** : Intégrer le formulaire et le JS de `03-FRONTEND/`
4. **Routes** : Ajouter les routes dans `routes/web.php` (voir `04-ROUTES.md`)
5. **Base de données** : Exécuter les migrations (voir `05-MIGRATIONS.md`)

---

## 🔄 Flux de paiement

```
Utilisateur remplit formulaire
        ↓
POST /init-don (ou /caisse)
        ↓
Choix : Mobile Money OU Carte bancaire
        ↓
┌─────────────────────────────────────────────────────────────┐
│ MOBILE MONEY                    │ CARTE BANCAIRE             │
│ → Appel API FlexPay Mobile      │ → Appel API FlexPay Card    │
│ → Polling /checkTransactionStatus│ → Redirection vers FlexPay  │
│ → Mise à jour statut            │ → Retour /paid/{ref}/...    │
└─────────────────────────────────────────────────────────────┘
```

---

## 📋 Prérequis

- Laravel 8+ (ou 9, 10, 11)
- Compte marchand FlexPay
- Token API FlexPay
