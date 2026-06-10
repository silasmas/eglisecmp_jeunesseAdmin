<x-mail::message>
# {{ __('retraite.mail_payment_failure_heading') }}

{{ __('retraite.mail_payment_failure_intro', [
    'name' => $participant?->full_name ?? '—',
    'event' => $payment?->event?->name ?? $participant?->event?->name ?? '—',
]) }}

**{{ __('retraite.mail_label_reference') }}** : {{ $alert->reference }}

@if ($payment)
**{{ __('retraite.mail_label_amount') }}** : {{ number_format((float) $payment->amount_expected, 2, ',', ' ') }} {{ $payment->currency }}

**Canal** : {{ $payment->channel ?? '—' }}
@endif

**Cause** : {{ $alert->message }}

**Source** : {{ $alert->failure_source }} ({{ $alert->failure_reason }})

{{ __('retraite.mail_payment_failure_action') }}

{{ __('retraite.mail_footer') }}
</x-mail::message>
