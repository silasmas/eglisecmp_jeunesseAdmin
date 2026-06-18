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
@endphp

# Merci pour votre don

Bonjour **{{ $donation->donor_name }}**,

Nous confirmons la bonne réception de votre don pour **{{ $eventName }}**.

**Référence :** {{ $donation->reference }}

@if($isInKind)
Votre proposition de **don en nature** a été transmise à l'équipe d'organisation. Nous vous recontacterons si nécessaire pour la coordination logistique.
@else
Votre **don en espèces** a bien été enregistré{{ $cashStatusSuffix }}

**Montant :** {{ number_format($displayAmount, 2) }} {{ $donation->currency }}
@endif

@if($isCashSubmitted)
Nous vous confirmerons par e-mail dès validation du paiement en espèces par l'administration.
@endif

@if($isSponsor && $isPaid)
Vous avez sponsorisé **{{ (int) $donation->youth_slots_count }}** jeune{{ (int) $donation->youth_slots_count > 1 ? 's' : '' }}.

Les jeunes concernés devront **vous demander le code parrainage** que nous vous communiquerons séparément (ou via l'équipe d'organisation). Ce code leur permettra de finaliser leur inscription sans payer les frais.
@endif

@if($donation->donor_message)
**Votre message :**
{{ $donation->donor_message }}
@endif

Merci pour votre générosité au service de la jeunesse CMP.
