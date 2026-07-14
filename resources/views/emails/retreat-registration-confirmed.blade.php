<x-mail::message>
@include('emails.partials.jeunesse-cmp-mail-header')

# {{ __('retraite.mail_greeting', ['name' => $participant->prenom]) }}

{{ __('retraite.mail_body_intro', ['event' => $event->name]) }}

**{{ __('retraite.mail_label_reference') }}** : {{ $payment->reference }}  
**{{ __('retraite.mail_label_amount') }}** : {{ $payment->amount_paid }} {{ $payment->currency }}

@if($showPlacements ?? false)
**{{ __('retraite.mail_label_room') }}** : {{ $participant->placementChambreLabel() }}

**{{ __('retraite.mail_label_workshop') }}** : {{ $participant->placementAtelierLabel() }}
@else
{{ $placementsPendingMessage ?? __('retraite.mail_placements_pending') }}
@endif

@include('emails.partials.cmp-mail-button', [
    'url' => $billetUrl,
    'label' => __('retraite.mail_button_billet'),
])

@if($hasParticipantDocuments ?? false)
{{ __('retraite.mail_billet_documents_hint') }}
@else
<p>{{ __('retraite.mail_billet_qr_hint') }}</p>
@endif

@include('emails.partials.cmp-mail-button', [
    'url' => $portalUrl,
    'label' => __('retraite.mail_button_portal'),
])

{{ __('retraite.mail_footer') }}

@include('emails.partials.cmp-mail-footer')

</x-mail::message>
