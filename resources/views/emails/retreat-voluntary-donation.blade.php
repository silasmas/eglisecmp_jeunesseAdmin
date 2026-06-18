@php
  $eventName = $donation->event?->name ?? 'Retraite';
  $isInKind = $donation->donation_kind === \App\Models\RetreatVoluntaryDonation::KIND_IN_KIND;
@endphp

# Nouveau don volontaire

Un don a été enregistré pour **{{ $eventName }}**.

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
**Objet :** {{ $donation->cash_purpose === \App\Models\RetreatVoluntaryDonation::PURPOSE_SPONSOR_YOUTH ? 'Places pour jeunes' : 'Bon fonctionnement de la retraite' }}

**Montant :** {{ number_format((float) $donation->amount_paid, 2) }} {{ $donation->currency }}

**Statut paiement :** {{ $donation->status }}
@endif

@if($isInKind && $donation->in_kind_description)
**Description du don :**
{{ $donation->in_kind_description }}
@endif

@if($donation->donor_message)
**Message :**
{{ $donation->donor_message }}
@endif

@if($donation->vouchers && $donation->vouchers->isNotEmpty())
**Codes parrainage générés :**

@foreach($donation->vouchers as $voucher)
- `{{ $voucher->code }}`
@endforeach
@endif

Consultez l’administration Filament — section **Dons volontaires** — pour le détail.
