@php
  $eventName = $donation->event?->name ?? 'Retraite';
  $isInKind = $donation->donation_kind === \App\Models\RetreatVoluntaryDonation::KIND_IN_KIND;
  $isSponsor = $donation->cash_purpose === \App\Models\RetreatVoluntaryDonation::PURPOSE_SPONSOR_YOUTH;
  $isCashSubmitted = $donation->status === \App\Models\RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED;
  $vouchers = $donation->relationLoaded('vouchers') ? $donation->vouchers : collect();
@endphp

<x-mail::message>
@include('emails.partials.jeunesse-cmp-mail-header')

@if($isCashSubmitted)
# Don cash à valider

Une preuve de paiement **en espèces** attend votre validation pour **{{ $eventName }}**.
@else
# Nouveau don volontaire

Un don a été enregistré pour **{{ $eventName }}**.
@endif

**Référence :** {{ $donation->reference }}

**Donateur :** {{ $donation->donor_name }}

@if($donation->donor_phone)
**Téléphone :** {{ $donation->donor_phone }}
@endif

@if($donation->donor_email)
**E-mail :** {{ $donation->donor_email }}
@endif

**Type :** {{ $isInKind ? 'Don en nature' : 'Don en espèces' }}

@if(!$isInKind)
**Objet :** {{ $isSponsor ? 'Prise en charge jeunes' : 'Bon fonctionnement de la retraite' }}

**Montant :** {{ number_format((float) ($donation->amount_paid > 0 ? $donation->amount_paid : $donation->amount_expected), 2, ',', ' ') }} {{ $donation->currency }}

**Statut :** {{ $donation->status }}

@if($isSponsor)
**Places jeunes :** {{ (int) $donation->youth_slots_count }}
@endif
@endif

@if($isInKind && $donation->in_kind_description)
**Description du don :**

{{ $donation->in_kind_description }}
@endif

@if($donation->donor_message)
**Message :**

{{ $donation->donor_message }}
@endif

@if($isCashSubmitted)
<x-mail::panel>
Validez ou rejetez ce paiement dans **Paiements don cash** ou **Dons volontaires**. Après validation, **remettez les codes parrainage au donateur en personne** — ils ne sont plus envoyés par e-mail au donateur.
</x-mail::panel>
@endif

@if($vouchers->isNotEmpty())
## Codes parrainage (administration uniquement)

Remettez ces codes au donateur **{{ $donation->donor_name }}** lors de son passage ou sur demande. **Ne pas les envoyer par e-mail au donateur.**

<x-mail::panel>
@foreach($vouchers as $voucher)
**{{ $voucher->code }}**
@endforeach
</x-mail::panel>
@endif

@if($adminDonationUrl)
@include('emails.partials.cmp-mail-button', [
    'url' => $adminDonationUrl,
    'label' => 'Ouvrir le don dans l\'administration',
])
@else
Consultez l'administration Filament — section **Dons volontaires** — pour le détail.
@endif

---

**Équipe Jeunesse CMP**

</x-mail::message>
