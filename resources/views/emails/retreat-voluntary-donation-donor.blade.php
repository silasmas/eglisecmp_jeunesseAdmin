@php
  $eventName = $donation->event?->name ?? 'Retraite';
  $isInKind = $donation->donation_kind === \App\Models\RetreatVoluntaryDonation::KIND_IN_KIND;
  $isSponsor = $donation->cash_purpose === \App\Models\RetreatVoluntaryDonation::PURPOSE_SPONSOR_YOUTH;
  $isPaid = $donation->status === \App\Models\RetreatVoluntaryDonation::STATUS_PAID;
  $isCashSubmitted = $donation->status === \App\Models\RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED;
  $displayAmount = (float) ($donation->amount_paid > 0 ? $donation->amount_paid : $donation->amount_expected);
  $portalUrl = \App\Support\RetreatMailUrl::inscription();
@endphp

<x-mail::message>
@include('emails.partials.jeunesse-cmp-mail-header')

@if($isCashSubmitted)
# Preuve de paiement reçue

Bonjour **{{ $donation->donor_name }}**,

Nous confirmons la bonne réception de votre **preuve de paiement en espèces** pour **{{ $eventName }}**.

**Référence :** {{ $donation->reference }}

**Montant indiqué :** {{ number_format($displayAmount, 2, ',', ' ') }} {{ $donation->currency }}

@if($isSponsor)
Vous avez demandé à sponsoriser **{{ (int) $donation->youth_slots_count }}** jeune{{ (int) $donation->youth_slots_count > 1 ? 's' : '' }}.
@endif

<x-mail::panel>
Votre dossier est **en attente de validation** par l'équipe d'administration. **Vous recevrez un e-mail dès que votre paiement aura été validé.**
</x-mail::panel>

Les codes parrainage (le cas échéant) ne sont **pas** transmis par e-mail : vous devrez vous rapprocher du **département jeunesse CMP** après validation pour les obtenir et les remettre aux jeunes concernés.

@else
# Merci pour votre don

Bonjour **{{ $donation->donor_name }}**,

Nous confirmons la bonne réception de votre don pour **{{ $eventName }}**.

**Référence :** {{ $donation->reference }}

@if($isInKind)
Votre proposition de **don en nature** a été transmise à l'équipe d'organisation. Nous vous recontacterons si nécessaire pour la coordination logistique.
@else
Votre **don en espèces** a bien été enregistré@if($isPaid) et le **paiement est confirmé**@endif.

**Montant :** {{ number_format($displayAmount, 2, ',', ' ') }} {{ $donation->currency }}
@endif

@if($isSponsor && $isPaid)
Vous avez sponsorisé **{{ (int) $donation->youth_slots_count }}** jeune{{ (int) $donation->youth_slots_count > 1 ? 's' : '' }}.

<x-mail::panel>
Votre paiement est **validé**. Pour obtenir les **codes parrainage** à remettre aux jeunes, rapprochez-vous du **département jeunesse CMP** ou de l'administration de la retraite.

Les codes **ne sont pas envoyés par e-mail** : seule l'équipe en charge pourra vous les remettre après vérification de votre dossier.
</x-mail::panel>

@include('emails.partials.cmp-mail-button', [
    'url' => $portalUrl,
    'label' => 'Portail d\'inscription (pour les jeunes)',
])

Indiquez aux jeunes qu'ils saisiront le code reçu auprès de l'administration à l'étape **Paiement** du formulaire d'inscription.
@endif
@endif

@if($donation->donor_message)
**Votre message :**

{{ $donation->donor_message }}
@endif

---

Cordialement,

**Équipe Jeunesse CMP**  
*Centre Missionnaire Philadelphie*

{{ __('retraite.mail_footer') }}

</x-mail::message>
