<x-mail::message>
# {{ __('retraite.mail_attendance_overdue_heading') }}

{{ __('retraite.mail_attendance_overdue_intro', [
    'activity' => $activityPlan->title,
    'event' => $activityPlan->session?->event?->name ?? 'Retraite',
    'deadline' => $deadline->format('d/m/Y H:i'),
]) }}

{{ __('retraite.mail_attendance_overdue_action') }}

@include('emails.partials.cmp-mail-button', [
    'url' => $portalUrl,
    'label' => 'Ouvrir l\'administration',
])
</x-mail::message>
