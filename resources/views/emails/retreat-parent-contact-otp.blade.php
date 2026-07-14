<x-mail::message>
@include('emails.partials.jeunesse-cmp-mail-header')

# {{ __('retraite.mail_otp_parent_heading') }}

Bonjour,

{{ __('retraite.mail_otp_parent_intro') }}

<x-mail::panel>
<span style="font-size: 28px; font-weight: 700; letter-spacing: 4px;">{{ $otp }}</span>
</x-mail::panel>

{{ __('retraite.mail_otp_expires', ['minutes' => $expiresInMinutes]) }}

@include('emails.partials.cmp-mail-footer', ['showSecurityHint' => true])

</x-mail::message>
