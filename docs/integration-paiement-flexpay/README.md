# Intégration Paiement FlexPay

Ce dossier contient tout le code et la documentation nécessaires pour intégrer le paiement FlexPay (Mobile Money + Carte bancaire) dans un projet Laravel.

**Utilisable pour :** Dons, inscriptions retraite, achats, abonnements, services, etc.

---

## ⚠️ Mobile Money — lecture obligatoire

Si vous intégrez ou corrigez le **Mobile Money**, commencez par :

**[`08-MOBILE-MONEY-CORRECTIFS.md`](08-MOBILE-MONEY-CORRECTIFS.md)**

Points clés (doc FlexPay v1.4) :

- API `type` = **`"1"`** pour **tout** Mobile Money (M-Pesa, Airtel, Orange, Afri…)
- **`"2"`** = carte bancaire (sur `paymentService`)
- **Ne pas** envoyer `type: "2"` pour Airtel ou `type: "3"` pour Orange
- La sélection d’opérateur dans l’UI sert à **valider le numéro** ; FlexPay route via le **`phone`**

---

## 📁 Structure du dossier

```
integration-paiement-flexpay/
├── README.md                         # Ce fichier
├── 01-CONFIGURATION.md               # Variables .env, config/services.php
├── 08-MOBILE-MONEY-CORRECTIFS.md     # ★ Correctifs type API + opérateurs UI
├── 02-BACKEND/
│   ├── README.md
│   ├── FlexPayService.php            # Carte bancaire
│   ├── FlexPayMobileHelper.php       # Helper Mobile Money (type "1")
│   ├── FlexPayMobileService.example.php  # Service Mobile Money (recommandé)
│   └── DonationPaymentController.php # Exemple dons
├── 03-FRONTEND/
│   ├── README.md
│   ├── formulaire-paiement.blade.php
│   ├── paiement.blade.php
│   └── paiement.js
├── 04-ROUTES.md
├── 05-MIGRATIONS.md
├── 06-ADAPTATION-DONS.md
└── 07-EXEMPLE-ENV.md
```

---

## 🚀 Démarrage rapide

1. **Lire** `08-MOBILE-MONEY-CORRECTIFS.md` si Mobile Money
2. **Configuration** : `.env` (voir `07-EXEMPLE-ENV.md` et `01-CONFIGURATION.md`)
3. **Backend** : copier `02-BACKEND/` dans votre projet
4. **Frontend** : intégrer `03-FRONTEND/`
5. **Routes** : `04-ROUTES.md`
6. **Base de données** : `05-MIGRATIONS.md`

---

## 🔄 Flux de paiement Mobile Money (corrigé)

```
Utilisateur choisit opérateur (UI) + saisit numéro 243…
        ↓
Validation locale (regex opérateur) — provider_code interne
        ↓
POST backend → FlexPay paymentService
        {
          "type": "1",          ← toujours "1" pour mobile
          "phone": "243…",
          "merchant", "amount", "reference", "callbackUrl"
        }
        ↓
FlexPay → push USSD / notification opérateur (selon numéro)
        ↓
Polling GET /check/{orderNumber} + webhook callbackUrl
        ↓
Statut payé / annulé / en attente
```

---

## 📋 Prérequis

- Laravel 8+ (ou 9, 10, 11, 12, 13)
- Compte marchand FlexPay
- Token API FlexPay (JWT)
- URLs de passerelle confirmées par FlexPay pour votre marchand
