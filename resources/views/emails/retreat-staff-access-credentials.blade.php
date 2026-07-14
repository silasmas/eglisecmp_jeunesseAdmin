<x-mail::message>
# {{ __('retraite.mail_staff_access_heading') }}

{{ __('retraite.mail_greeting', ['name' => $user->name]) }}

{{ __('retraite.mail_staff_access_intro') }}

@if($assignmentSummary)
**{{ __('retraite.mail_staff_access_assignment_label') }}** : {{ $metierRoleLabel ?? __('retraite.mail_staff_access_role_responsable') }} — {{ $assignmentSummary }}
@endif

**{{ __('retraite.mail_staff_access_role_label') }}** : {{ $dashboardRoleLabel }}

<x-mail::panel>
**{{ __('retraite.mail_staff_access_email_label') }}** : {{ $loginEmail }}

**{{ __('retraite.mail_staff_access_password_label') }}** : {{ $plainPassword }}
</x-mail::panel>

{{ __('retraite.mail_staff_access_security') }}

@include('emails.partials.cmp-mail-button', [
    'url' => $adminUrl,
    'label' => __('retraite.mail_staff_access_button'),
])

{{ __('retraite.mail_footer') }}
</x-mail::message>
