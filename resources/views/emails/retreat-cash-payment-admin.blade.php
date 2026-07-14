<x-mail::message>
@include('emails.partials.jeunesse-cmp-mail-header')

# {{ __('retraite.mail_admin_cash_heading') }}

{{ __('retraite.mail_greeting', ['name' => '']) }}

{{ __('retraite.mail_admin_cash_intro', ['name' => $participant->full_name, 'event' => $event->name]) }}

**{{ __('retraite.mail_label_reference') }}** : {{ $payment->reference }}

**{{ __('retraite.mail_label_amount') }}** : {{ number_format((float) $payment->amount_expected, 2, ',', ' ') }} {{ $payment->currency }}

{{ __('retraite.mail_admin_cash_action') }}

@include('emails.partials.cmp-mail-footer')

</x-mail::message>
