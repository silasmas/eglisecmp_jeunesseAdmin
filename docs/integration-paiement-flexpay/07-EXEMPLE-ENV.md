# Exemple .env complet

```env
APP_NAME="Mon Projet"
APP_URL=https://votre-domaine.com

# ─── FlexPay — Paiement Mobile Money + Carte ───
FLEXPAY_API_TOKEN=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
FLEXPAY_MARCHAND=YOUR_MERCHANT_CODE

# URLs : confirmer avec FlexPay (paymentService vs /mobile selon marchand)
FLEXPAY_GATEWAY_MOBILE=https://backend.flexpay.cd/api/rest/v1/paymentService
FLEXPAY_GATEWAY_CARD=https://cardpayment.flexpay.cd/v1.1/pay
FLEXPAY_GATEWAY_CHECK=https://backend.flexpay.cd/api/rest/v1/check

# Opérateurs affichés dans l’UI (JSON — guillemets simples obligatoires)
# Le "type" ici est un id interne (mpesa, orange…), PAS le type API FlexPay (toujours "1" pour mobile)
FLEXPAY_MOBILE_PROVIDERS='[{"type":"mpesa","code":"mpesa","label":"M-Pesa","msisdn_regex":"^2438[123][0-9]{7}$"},{"type":"airtel","code":"airtel","label":"Airtel Money","msisdn_regex":"^2439[0-9]{8}$"},{"type":"orange","code":"orange","label":"Orange Money","msisdn_regex":"^2438[459][0-9]{7}$"},{"type":"afri","code":"afri","label":"Afri Money","msisdn_regex":"^2439[0-9]{8}$"}]'
```

---

## Rappels Mobile Money

| Sujet | Valeur |
|-------|--------|
| Type API FlexPay (Mobile Money) | **`"1"`** pour tous les opérateurs |
| Numéro `phone` | 12 chiffres, `243` + 9 chiffres, sans `+` |
| Erreur « type envoyé ne correspond pas » | Souvent `type: "3"` ou `"2"` envoyé à la place de `"1"` — voir [`08-MOBILE-MONEY-CORRECTIFS.md`](08-MOBILE-MONEY-CORRECTIFS.md) |

> ⚠️ **Ne jamais commiter** le fichier `.env` avec vos vrais tokens.
