<x-mail::message>
@include('emails.partials.jeunesse-cmp-mail-header')

# {{ $title }}

Bonjour,

{{ $message }}

{{ __('retraite.mail_assignment_confirmation') }}

@include('emails.partials.cmp-mail-footer')

</x-mail::message>
