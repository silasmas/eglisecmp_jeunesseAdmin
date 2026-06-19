<x-mail::message>
# {{ __('retraite.mail_greeting', ['name' => $participant->prenom]) }}

{{ __('retraite.mail_body_intro', ['event' => $event->name]) }}

**{{ __('retraite.mail_label_reference') }}** : {{ $payment->reference }}  
**{{ __('retraite.mail_label_amount') }}** : {{ $payment->amount_paid }} {{ $payment->currency }}

**{{ __('retraite.mail_label_room') }}** : {{ $participant->placementChambreLabel() }}

**{{ __('retraite.mail_label_workshop') }}** : {{ $participant->placementAtelierLabel() }}

@include('emails.partials.cmp-mail-button', [
    'url' => $billetUrl,
    'label' => __('retraite.mail_button_billet'),
])

<p>Conservez ce billet : le QR code permettra de vérifier votre inscription à l'accueil.</p>

@include('emails.partials.cmp-mail-button', [
    'url' => $portalUrl,
    'label' => __('retraite.mail_button_portal'),
])

{{ __('retraite.mail_footer') }}

</x-mail::message>
