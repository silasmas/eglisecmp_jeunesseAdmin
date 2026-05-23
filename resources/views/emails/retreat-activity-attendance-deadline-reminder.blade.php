<x-mail::message>
# {{ __('retraite.mail_attendance_reminder_heading') }}

{{ __('retraite.mail_attendance_reminder_intro', [
    'activity' => $activityPlan->title,
    'event' => $activityPlan->session?->event?->name ?? 'Retraite',
    'deadline' => $deadline->format('d/m/Y H:i'),
]) }}

{{ __('retraite.mail_attendance_reminder_action') }}
</x-mail::message>
