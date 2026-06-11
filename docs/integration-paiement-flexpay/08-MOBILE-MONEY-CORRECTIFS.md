# Mobile Money FlexPay — correctifs et bonnes pratiques (v1.4)

> **À lire en priorité** si vous intégrez ou maintenez FlexPay Mobile Money en RDC (M-Pesa, Airtel, Orange, Afri Money).
> Ce guide corrige une erreur fréquente : confondre le **type API FlexPay** avec un **code opérateur**.

Référence officielle : *FlexPay API Documentation v1.4* — endpoint `POST /api/rest/v1/paymentService`.

---

## 1. Le champ `type` de l’API (doc FlexPay v1.4)

Sur **`paymentService`**, FlexPay n’attend **que deux valeurs** :

| Valeur `type` | Signification |
|---------------|---------------|
| **`"1"`** | **Mobile Money** (tous opérateurs) |
| **`"2"`** | **Carte bancaire** (si ce canal passe par le même endpoint ; la carte utilise souvent aussi `cardpayment…/v1|v2/pay`) |

**FlexPay ne documente pas** de types séparés pour M-Pesa (`1`), Airtel (`2`), Orange (`3`), etc.

L’opérateur est déterminé par le **numéro de téléphone** (`phone`), pas par une valeur différente de `type`.

### Exemple de requête correcte (Mobile Money)

```json
{
  "merchant": "VOTRE_MARCHAND",
  "type": "1",
  "phone": "243891234567",
  "reference": "REF-20260609-ABC",
  "amount": "10",
  "currency": "USD",
  "callbackUrl": "https://votre-domaine.com/api/payment/callback"
}
```

Headers :

```
Content-Type: application/json
Authorization: Bearer VOTRE_TOKEN_JWT
```

---

## 2. Erreur fréquente : « le type envoyé ne correspond pas »

### Symptôme

FlexPay renvoie un message du type :

> *Erreur, le type envoyé ne correspond pas*

### Cause

L’application envoie **`type: "2"`, `"3"`, `"4"`…** en pensant identifier l’opérateur (Airtel, Orange…).

FlexPay interprète **`"2"` comme carte bancaire**, et **`"3"`** (etc.) comme **invalide** pour Mobile Money.

### Correctif

| Couche | Action |
|--------|--------|
| **Backend** | Toujours envoyer **`type: "1"`** à FlexPay pour tout paiement Mobile Money |
| **Frontend** | Conserver la sélection d’opérateur **uniquement pour l’UX** (aide, regex du numéro) |
| **Validation API interne** | Utiliser un identifiant opérateur (`provider_code` : `mpesa`, `orange`…) — **ne pas** le transmettre tel quel dans `type` FlexPay |

---

## 3. Architecture recommandée (multi-projets)

Séparer **deux concepts** :

```
┌─────────────────────────────────────────────────────────────┐
│  UI / validation interne                                     │
│  provider_code : mpesa | airtel | orange | afri             │
│  → regex MSISDN, libellé, opérateur masquable en admin      │
└───────────────────────────┬─────────────────────────────────┘
                            │ Backend traduit
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  Appel FlexPay paymentService                                │
│  type : "1"  (toujours, pour Mobile Money)                  │
│  phone : 243XXXXXXXXX (12 chiffres, sans +)                 │
└─────────────────────────────────────────────────────────────┘
```

### Constantes de configuration (exemple Laravel)

```php
// config/flexpay.php ou config/retraite.php selon le projet
return [
    'flexpay_mobile_money_api_type' => '1',
    'flexpay_card_api_type' => '2',

    'flexpay_mobile_providers' => [
        // "type" = identifiant UI interne, PAS le type API FlexPay
        ['type' => 'mpesa',  'code' => 'mpesa',  'label' => 'M-Pesa',       'msisdn_regex' => '^2438[123][0-9]{7}$'],
        ['type' => 'airtel', 'code' => 'airtel', 'label' => 'Airtel Money', 'msisdn_regex' => '^2439[0-9]{8}$'],
        ['type' => 'orange', 'code' => 'orange', 'label' => 'Orange Money', 'msisdn_regex' => '^2438[459][0-9]{7}$'],
        ['type' => 'afri',   'code' => 'afri',   'label' => 'Afri Money',   'msisdn_regex' => '^2439[0-9]{8}$'],
    ],
];
```

### Exemple backend (contrôleur)

```php
// Le client envoie provider_code (ex. "orange") pour validation UI
$validated = $request->validate([
    'phone' => ['required', 'string', 'max:30'],
    'provider_code' => ['required', 'string', 'max:32'], // pas max:5 si codes texte !
]);

$this->assertProviderAllowed($validated['provider_code']);
$this->assertMsisdnMatchesProvider($validated['provider_code'], $normalizedPhone);

// Appel FlexPay : type API fixe
$result = $flexPayMobile->initiateMobilePayment(
    $reference,
    $amount,
    $currency,
    $normalizedPhone,
    config('flexpay.flexpay_mobile_money_api_type', '1') // toujours "1"
);
```

> **Projet retraite (eglisecmp_jeunesseAdmin)** : le paramètre s’appelle encore `flexpay_type` côté front pour compatibilité, mais le serveur envoie **`"1"`** à FlexPay. Voir `FlexPayMobileService` et `RetreatPublicRegistrationController::initMobilePayment`.

---

## 4. Format du numéro (`phone`)

| Règle | Détail |
|-------|--------|
| Format | **12 chiffres**, préfixe **`243`**, **sans** `+` ni `0` national initial |
| Exemple | `243891234567`, `243850026476` |
| Validation UI | Regex par opérateur (`msisdn_regex`) — aide l’utilisateur, n’est **pas** un second `type` FlexPay |

Normalisation côté serveur recommandée :

```php
// 0891234567 → 243891234567
// +243891234567 → 243891234567
```

---

## 5. URLs des passerelles

Selon le **contrat marchand**, FlexPay peut fournir des URLs différentes. Vérifier avec Infoset / FlexPay :

| Usage | URL courante | Exemple marchand DGRAD |
|-------|--------------|------------------------|
| Mobile Money | `/api/rest/v1/paymentService` ou `/api/rest/v1/mobile` | `https://backend.flexpay.cd/api/rest/v1/paymentService` |
| Carte | `cardpayment.flexpay.cd/v1/pay` ou `/v2/pay` | `https://cardpayment.flexpay.cd/v1.1/pay` |
| Vérification | `/api/rest/v1/check/{orderNumber}` | `https://backend.flexpay.cd/api/rest/v1/check` |

Variables `.env` :

```env
FLEXPAY_GATEWAY_MOBILE=https://backend.flexpay.cd/api/rest/v1/paymentService
FLEXPAY_GATEWAY_CARD=https://cardpayment.flexpay.cd/v1.1/pay
FLEXPAY_GATEWAY_CHECK=https://backend.flexpay.cd/api/rest/v1/check
```

---

## 6. Variables `.env` — opérateurs affichés (optionnel)

Pour personnaliser la liste des réseaux **côté interface** (pas pour FlexPay API) :

```env
# JSON entre guillemets simples obligatoires (sinon erreur Dotenv)
FLEXPAY_MOBILE_PROVIDERS='[{"type":"mpesa","code":"mpesa","label":"M-Pesa","msisdn_regex":"^2438[123][0-9]{7}$"},{"type":"airtel","code":"airtel","label":"Airtel Money","msisdn_regex":"^2439[0-9]{8}$"},{"type":"orange","code":"orange","label":"Orange Money","msisdn_regex":"^2438[459][0-9]{7}$"},{"type":"afri","code":"afri","label":"Afri Money","msisdn_regex":"^2439[0-9]{8}$"}]'
```

**Projet retraite** : variable équivalente `RETRAITE_FLEXPAY_MOBILE_PROVIDERS`.

Erreur typique si JSON non quoté :

```
Failed to parse dotenv file. Encountered unexpected whitespace at [[{...
```

---

## 7. Autres erreurs Mobile Money (après correctif `type`)

| Message / situation | Niveau | Piste |
|-------------------|--------|-------|
| *Certaines informations sont invalides…* | Validation Laravel **avant** FlexPay | `provider_code` trop long (`max:5` avec `"airtel"` / `"orange"`) → utiliser `max:32` |
| *Numéro ne correspond pas au réseau* | App / validation regex | Mauvais opérateur choisi ou numéro incorrect |
| Push non reçu | Opérateur / client | Solde, téléphone éteint, timeout USSD |
| `code != 0` FlexPay | API FlexPay | Marchand, montant, devise, contrat opérateur |

---

## 8. Anti-patterns à éviter

| ❌ Incorrect | ✅ Correct |
|-------------|-----------|
| `'type' => '3'` pour Orange | `'type' => '1'` + `phone` Orange |
| `'type' => '2'` pour Airtel Money | `'type' => '1'` ( `"2"` = carte sur paymentService ) |
| Mapper M-Pesa=1, Airtel=2 dans l’API FlexPay | Identifiants UI `mpesa`, `airtel`… + API `type` = `1` |
| `flexpay_type` max 5 caractères | `max:32` pour codes texte |
| JSON `.env` sans guillemets | `'[{...}]'` entre guillemets simples |

---

## 9. Checklist migration d’un ancien projet

- [ ] Rechercher `'type' =>` dans les appels Mobile Money (helpers, services, contrôleurs)
- [ ] Remplacer toute valeur opérateur (`2`, `3`, `4`…) par **`'1'`** pour FlexPay
- [ ] Renommer / documenter le champ UI : `provider_code` ou garder `flexpay_type` comme **id interne**
- [ ] Assouplir la validation Laravel (`max:32` sur le code opérateur)
- [ ] Vérifier `FLEXPAY_GATEWAY_MOBILE` avec FlexPay (paymentService vs mobile)
- [ ] Tester Orange, Airtel, M-Pesa avec la page **Test FlexPay** admin (projet retraite) ou un appel Postman
- [ ] Mettre à jour la doc interne du projet (ce fichier)

---

## 10. Références dans ce dépôt

| Fichier | Rôle |
|---------|------|
| `config/retraite.php` | Constantes `flexpay_mobile_money_api_type` + liste opérateurs UI |
| `app/Services/FlexPay/FlexPayMobileService.php` | Envoi `type: "1"` à paymentService |
| `app/Http/Controllers/Api/RetreatPublicRegistrationController.php` | Validation `provider` + init paiement |
| `02-BACKEND/DonationPaymentController.php` | Exemple dons — déjà `'type' => '1'` |
| `07-EXEMPLE-ENV.md` | Variables d’environnement à jour |
