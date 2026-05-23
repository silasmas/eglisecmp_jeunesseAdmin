<x-mail::message>
# {{ __('retraite.mail_greeting', ['name' => $participant->prenom]) }}

{{ __('retraite.mail_body_intro', ['event' => $event->name]) }}

**{{ __('retraite.mail_label_reference') }}** : {{ $payment->reference }}  
**{{ __('retraite.mail_label_amount') }}** : {{ $payment->amount_paid }} {{ $payment->currency }}

@if($participant->chambre)
**{{ __('retraite.mail_label_room') }}** : {{ $participant->chambre->nom }}
@endif

@if($participant->atelier)
**{{ __('retraite.mail_label_workshop') }}** : {{ __('retraite.mail_workshop_number', ['n' => $participant->atelier->numero]) }}
@endif

<x-mail::button :url="$billetUrl">
{{ __('retraite.mail_button_billet') }}
</x-mail::button>

<p>Conservez ce billet : le QR code permettra de vérifier votre inscription à l'accueil.</p>

<x-mail::button :url="config('app.url')">
{{ __('retraite.mail_button_portal') }}
</x-mail::button>

{{ __('retraite.mail_footer') }}

</x-mail::message>
