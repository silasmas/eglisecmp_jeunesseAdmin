<x-mail::message>
# {{ __('retraite.mail_atelier_report_heading') }}

{{ __('retraite.mail_atelier_report_intro', [
    'submitter' => $submitterName,
    'atelier' => $atelier->numero,
    'activity' => $activityPlan->title,
]) }}

@if ($report->sujet)
**Sujet :** {{ $report->sujet }}
@endif

@if ($report->texte_biblique)
**Texte biblique :** {{ $report->texte_biblique }}
@endif

@if ($report->resume)
**Résumé :** {{ $report->resume }}
@endif

@if (is_array($report->conducteurs) && count($report->conducteurs))
@php
    $conducteursOuvriers = collect($report->conducteurs)->where('type', 'user')->pluck('label')->filter()->join(', ');
    $conducteursParticipants = collect($report->conducteurs)->where('type', 'participant')->pluck('label')->filter()->join(', ');
    $conducteursDebat = collect($report->conducteurs)->whereIn('type', ['debat_user', 'debat_participant'])->pluck('label')->filter()->join(', ');
@endphp
@if ($conducteursOuvriers)
**Conducteur(s) ouvrier(s) :** {{ $conducteursOuvriers }}
@endif
@if ($conducteursParticipants)
**Conducteur(s) participant(s) :** {{ $conducteursParticipants }}
@endif
@if ($conducteursDebat)
**Conducteur(s) du débat :** {{ $conducteursDebat }}
@endif
@endif

{{ __('retraite.mail_atelier_report_footer') }}
</x-mail::message>
