<x-mail::message>
# {{ __('retraite.mail_staff_assignment_heading') }}

{{ __('retraite.mail_greeting', ['name' => $user->name]) }}

{{ __('retraite.mail_staff_assignment_intro', [
    'role' => $roleLabel,
    'type' => $assignmentType === 'atelier' ? __('retraite.mail_staff_assignment_type_atelier') : __('retraite.mail_staff_assignment_type_chambre'),
    'label' => $assignmentType === 'atelier'
        ? __('retraite.mail_staff_assignment_atelier', ['numero' => $assignment->numero])
        : __('retraite.mail_staff_assignment_chambre', ['nom' => $assignment->nom]),
    'retreat' => $retreatTitle,
]) }}

{{ __('retraite.mail_staff_assignment_body') }}

@include('emails.partials.cmp-mail-button', [
    'url' => $adminUrl,
    'label' => __('retraite.mail_staff_assignment_button'),
])

{{ __('retraite.mail_staff_assignment_credentials_heading') }}

**{{ __('retraite.mail_staff_access_role_label') }}** : {{ $dashboardRoleLabel }}

<x-mail::panel>
**{{ __('retraite.mail_staff_access_email_label') }}** : {{ $loginEmail }}

**{{ __('retraite.mail_staff_access_password_label') }}** : {{ $plainPassword }}
</x-mail::panel>

{{ __('retraite.mail_staff_access_security') }}

{{ __('retraite.mail_footer') }}
</x-mail::message>
