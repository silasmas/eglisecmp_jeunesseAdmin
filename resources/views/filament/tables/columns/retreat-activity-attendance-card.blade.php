@php
    /** @var \App\Models\RetreatActivityAttendance $record */
    $record = $getRecord();
    $participant = $record->participant;
    $activityPlan = $record->activityPlan;
    $fullName = trim(($participant?->prenom ?? '').' '.($participant?->nom ?? '')) ?: 'Participant';
    $photo = $participant?->photo;
    $photoUrl = filled($photo)
        ? (str_starts_with($photo, 'http') ? $photo : asset('storage/'.$photo))
        : 'https://ui-avatars.com/api/?name='.urlencode($fullName).'&background=7b1d3e&color=fff';
    $participantUrl = $participant
        ? \App\Filament\Resources\RetreatParticipants\RetreatParticipantResource::getUrl('view', ['record' => $participant])
        : null;
    $status = $record->status ?? 'absent';
    $statusLabel = [
        'present' => 'Present',
        'absent' => 'Absent',
        'excused' => 'Excuse',
        'late' => 'En retard',
    ][$status] ?? ucfirst($status);
    $statusColor = [
        'present' => '#22c55e',
        'absent' => '#ef4444',
        'excused' => '#3b82f6',
        'late' => '#f59e0b',
    ][$status] ?? '#6b7280';
    $details = [
        'Pointage' => $statusLabel,
        'Evenement' => $activityPlan?->session?->event?->name,
        'Activite' => $activityPlan?->title,
        'Chambre' => $participant?->chambre?->nom,
        'Resp. chambre' => $participant?->chambre?->responsable?->name,
        'Atelier' => $participant?->atelier?->numero,
        'Resp. atelier' => $participant?->atelier?->responsable?->name,
        'Entree' => $record->check_in_at?->format('d/m/Y H:i'),
        'Sortie' => $record->check_out_at?->format('d/m/Y H:i'),
        'Source' => $record->scan_source,
        'Enregistre par' => $record->recorder?->name,
        ($status === 'excused' ? 'Motif excuse' : 'Note') => $record->note,
    ];
@endphp

<div
    data-attendance-card-content
    style="position: relative; display: flex; width: 100%; flex-direction: column; align-items: center; justify-content: center; gap: .35rem; padding: 2rem .65rem .85rem; text-align: center;"
>
    @if ($participantUrl)
        <a
            href="{{ $participantUrl }}"
            target="_blank"
            rel="noopener noreferrer"
            data-attendance-photo="{{ $photoUrl }}"
            data-attendance-photo-alt="Apercu {{ $fullName }}"
            style="display: inline-flex; cursor: pointer;"
            title="Ouvrir le profil de {{ $fullName }}"
            onmouseenter="window.cmpShowAttendanceImagePreview(this, event)"
            onmousemove="window.cmpMoveAttendanceImagePreview(event)"
            onmouseleave="window.cmpHideAttendanceImagePreview()"
            onfocus="window.cmpShowAttendanceImagePreview(this)"
            onblur="window.cmpHideAttendanceImagePreview()"
        >
            <img
                src="{{ $photoUrl }}"
                alt="Profil {{ $fullName }}"
                style="height: 3rem; width: 3rem; border-radius: 9999px; object-fit: cover; border: 2px solid #f3d7e3;"
                onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($fullName) }}&background=7b1d3e&color=fff'"
            >
        </a>
    @else
        <img
            src="{{ $photoUrl }}"
            alt="Profil {{ $fullName }}"
            data-attendance-photo="{{ $photoUrl }}"
            data-attendance-photo-alt="Apercu {{ $fullName }}"
            style="height: 3rem; width: 3rem; border-radius: 9999px; object-fit: cover; border: 2px solid #f3d7e3; cursor: zoom-in;"
            onmouseenter="window.cmpShowAttendanceImagePreview(this, event)"
            onmousemove="window.cmpMoveAttendanceImagePreview(event)"
            onmouseleave="window.cmpHideAttendanceImagePreview()"
            onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($fullName) }}&background=7b1d3e&color=fff'"
        >
    @endif

    <div>
        <div
            style="font-size: .78rem; font-weight: 700; line-height: 1.2; color: #111827; cursor: help;"
            onmouseenter="window.cmpShowAttendancePopover(this)"
            onmouseleave="window.cmpHideAttendancePopover()"
            onfocus="window.cmpShowAttendancePopover(this)"
            onblur="window.cmpHideAttendancePopover()"
            tabindex="0"
        >
            {{ $fullName }}
        </div>

        <div style="display: flex; justify-content: center; margin-top: .25rem;">
            <span
                style="display: inline-flex; height: .72rem; width: .72rem; border-radius: 9999px; background-color: {{ $statusColor }}; border: 2px solid #ffffff; box-shadow: 0 0 0 1px rgba(17, 24, 39, .08);"
                title="{{ $statusLabel }}"
                aria-label="{{ $statusLabel }}"
            ></span>
        </div>

        @if ($record->recorder)
            <div style="margin-top: .2rem; font-size: .62rem; color: #6b7280; line-height: 1.2;">
                Par {{ $record->recorder->name }}
                @if ($record->updated_at)
                    · {{ $record->updated_at->format('d/m H:i') }}
                @endif
            </div>
        @endif

        @if ($status === 'excused' && filled($record->note))
            <div style="margin-top: .25rem; font-size: .62rem; color: #2563eb; line-height: 1.2; max-width: 8rem; overflow: hidden; text-overflow: ellipsis;" title="{{ $record->note }}">
                {{ $record->note }}
            </div>
        @endif
    </div>

    <template data-attendance-popover-template>
        <div style="display: grid; grid-template-columns: repeat(2, minmax(7.5rem, 1fr)); column-gap: 1rem; row-gap: .45rem;">
            @foreach ($details as $label => $value)
                <div style="min-width: 7.5rem;">
                    <div style="font-size: .68rem; font-weight: 600; color: #6b7280;">{{ $label }}</div>
                    <div style="font-size: .78rem; color: #111827; white-space: nowrap;">{{ filled($value) ? $value : '-' }}</div>
                </div>
            @endforeach
        </div>
    </template>
</div>
