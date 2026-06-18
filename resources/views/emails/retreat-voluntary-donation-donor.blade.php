@php
  $eventName = $donation->event?->name ?? 'Retraite';
  $isInKind = $donation->donation_kind === \App\Models\RetreatVoluntaryDonation::KIND_IN_KIND;
  $isSponsor = $donation->cash_purpose === \App\Models\RetreatVoluntaryDonation::PURPOSE_SPONSOR_YOUTH;
  $isPaid = $donation->status === \App\Models\RetreatVoluntaryDonation::STATUS_PAID;
  $isCashSubmitted = $donation->status === \App\Models\RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED;
  $cashStatusSuffix = match (true) {
    $isPaid => ' et le paiement est confirmé.',
    $isCashSubmitted => ' : votre preuve est en cours de validation par l\'équipe.',
    default => '.',
  };
  $displayAmount = (float) ($donation->amount_paid > 0 ? $donation->amount_paid : $donation->amount_expected);
  $vouchers = $donation->relationLoaded('vouchers') ? $donation->vouchers : collect();
  $portalUrl = rtrim((string) config('app.url'), '/').'/inscription-retraite';
@endphp

<x-mail::message>
@include('emails.partials.jeunesse-cmp-mail-header')

# Merci pour votre don

Bonjour **{{ $donation->donor_name }}**,

Nous confirmons la bonne réception de votre don pour **{{ $eventName }}**.

**Référence :** {{ $donation->reference }}

@if($isInKind)
Votre proposition de **don en nature** a été transmise à l'équipe d'organisation. Nous vous recontacterons si nécessaire pour la coordination logistique.
@else
Votre **don en espèces** a bien été enregistré{{ $cashStatusSuffix }}

**Montant :** {{ number_format($displayAmount, 2, ',', ' ') }} {{ $donation->currency }}
@endif

@if($isCashSubmitted)
Nous vous enverrons un **second e-mail de confirmation** (avec les codes parrainage le cas échéant) dès validation du paiement en espèces par l'administration.
@endif

@if($isSponsor && $isPaid)
Vous avez sponsorisé **{{ (int) $donation->youth_slots_count }}** jeune{{ (int) $donation->youth_slots_count > 1 ? 's' : '' }}.

@if($vouchers->isNotEmpty())
## Codes parrainage à transmettre

Chaque jeune doit saisir **un code distinct** lors de l'étape **Paiement** sur le portail d'inscription (après avoir rempli le formulaire).

<x-mail::panel>
@foreach($vouchers as $voucher)
**{{ $voucher->code }}**
@endforeach
</x-mail::panel>

<x-mail::button :url="$portalUrl">
Ouvrir le portail d'inscription
</x-mail::button>

Le code couvre les frais d'inscription pour **une place**. Conservez une copie de ces codes pour les transmettre aux jeunes concernés.
@else
Les codes parrainage seront générés sous peu. Contactez l'équipe d'organisation si vous ne les recevez pas.
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
