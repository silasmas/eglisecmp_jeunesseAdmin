# Exemple .env complet

```env
APP_NAME="Mon Projet Dons"
APP_URL=https://votre-domaine.com

# FlexPay - Paiement Mobile Money + Carte bancaire
FLEXPAY_API_TOKEN=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
FLEXPAY_MARCHAND=YOUR_MERCHANT_CODE
FLEXPAY_GATEWAY_MOBILE=https://backend.flexpay.cd/api/rest/v1/mobile
FLEXPAY_GATEWAY_CARD=https://backend.flexpay.cd/api/rest/v1/card
```

> ⚠️ **Important** : Ne jamais commiter le fichier `.env` avec vos vrais tokens.
