@php
    /** @var \App\Models\RetreatActivityPlan $record */
    $record = $getRecord();
    $statusLabel = [
        'planned' => 'Planifie',
        'ongoing' => 'En cours',
        'done' => 'Termine',
        'cancelled' => 'Annule',
    ][$record->status] ?? ucfirst((string) $record->status);
    $statusColor = [
        'planned' => '#f59e0b',
        'ongoing' => '#3b82f6',
        'done' => '#22c55e',
        'cancelled' => '#ef4444',
    ][$record->status] ?? '#6b7280';
    $timeRange = trim(($record->starts_at?->format('H:i') ?? '--:--').' - '.($record->ends_at?->format('H:i') ?? '--:--'));
@endphp

<div style="position: relative; display: grid; gap: .8rem; {{ filled($record->notes) ? 'padding-right: 11rem;' : '' }}">
    @if (filled($record->notes))
        <div style="position: absolute; top: 0; right: 0; max-width: 10rem; border-radius: .65rem; background: #f9fafb; padding: .55rem .65rem; font-size: .72rem; line-height: 1.25; color: #4b5563;">
            <div style="font-weight: 700; color: #111827; margin-bottom: .2rem;">Description</div>
            <div style="display: -webkit-box; -webkit-line-clamp: 5; -webkit-box-orient: vertical; overflow: hidden;">
                {{ $record->notes }}
            </div>
        </div>
    @endif

    <div>
        <h3 style="font-size: 1rem; font-weight: 800; color: #111827; margin: 0;">
            {{ $record->title }}
        </h3>

        <div style="display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .55rem; font-size: .75rem; color: #4b5563;">
            <span style="border-radius: 9999px; background: #f3f4f6; padding: .22rem .55rem;">
                {{ $record->session?->event?->name ?? 'Evenement non defini' }}
            </span>
            <span style="border-radius: 9999px; background: #f3f4f6; padding: .22rem .55rem;">
                {{ $record->activity_type }}
            </span>
            <span style="border-radius: 9999px; background: #f3f4f6; padding: .22rem .55rem;">
                {{ $record->location ?: 'Lieu non defini' }}
            </span>
        </div>
    </div>

    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; font-size: .78rem;">
        <div style="display: inline-flex; align-items: center; gap: .4rem; color: #4b5563;">
            <span style="display: inline-flex; height: .55rem; width: .55rem; border-radius: 9999px; background: {{ $statusColor }};"></span>
            {{ $statusLabel }}
        </div>

        <div style="font-weight: 700; color: #111827;">
            {{ $timeRange }}
        </div>

        <div style="color: #6b7280;">
            Obligatoire: {{ $record->is_mandatory ? 'Oui' : 'Non' }}
        </div>
    </div>
</div>
