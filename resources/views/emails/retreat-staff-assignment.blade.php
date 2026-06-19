<x-mail::message>
# {{ __('retraite.mail_staff_assignment_heading') }}

{{ __('retraite.mail_greeting', ['name' => $user->name]) }}

{{ __('retraite.mail_staff_assignment_intro', [
    'role' => $roleLabel,
    'type' => $assignmentType === 'atelier' ? __('retraite.mail_staff_assignment_type_atelier') : __('retraite.mail_staff_assignment_type_chambre'),
    'label' => $assignmentType === 'atelier'
        ? __('retraite.mail_staff_assignment_atelier', ['numero' => $assignment->numero])
        : __('retraite.mail_staff_assignment_chambre', ['nom' => $assignment->nom]),
]) }}

{{ __('retraite.mail_staff_assignment_body') }}

@include('emails.partials.cmp-mail-button', [
    'url' => $adminUrl,
    'label' => __('retraite.mail_staff_assignment_button'),
])

{{ __('retraite.mail_footer') }}
</x-mail::message>
