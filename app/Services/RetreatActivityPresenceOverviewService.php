<?php

namespace App\Services;

use App\Models\RetreatActivityAttendance;
use App\Models\RetreatActivityPlan;
use App\Models\RetreatAtelier;
use App\Models\RetreatParticipant;

/**
 * Agrège les présences par activité et par atelier pour la synthèse admin.
 */
class RetreatActivityPresenceOverviewService
{
    /**
     * @param int $activityPlanId Identifiant du plan d'activité
     * @return array{
     *     activity: array{id: int, label: string},
     *     totals: array{
     *         participants: int,
     *         present: int,
     *         late: int,
     *         absent: int,
     *         excused: int,
     *         unmarked: int,
     *         marked: int,
     *         present_effective: int,
     *         present_rate: float,
     *         pointage_rate: float,
     *         ateliers_count: int
     *     },
     *     rows: list<array{
     *         atelier_id: int,
     *         atelier_numero: int,
     *         age_range: string,
     *         responsable: string|null,
     *         participants: int,
     *         present: int,
     *         late: int,
     *         absent: int,
     *         excused: int,
     *         unmarked: int,
     *         present_rate: float
     *     }>
     * }
     */
    public function buildForActivity(int $activityPlanId): array
    {
        $plan = RetreatActivityPlan::query()
            ->with('session.event')
            ->findOrFail($activityPlanId);

        $placement = app(RetreatPlacementAssignmentService::class);

        $totals = [
            'participants' => 0,
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'excused' => 0,
            'unmarked' => 0,
        ];

        $rows = [];

        $ateliers = RetreatAtelier::query()
            ->where('is_active', true)
            ->with('responsable')
            ->orderBy('numero')
            ->get();

        foreach ($ateliers as $atelier) {
            $participantIds = RetreatParticipant::query()
                ->where('atelier_id', $atelier->id)
                ->where('is_active', true)
                ->pluck('id');

            if ($participantIds->isEmpty()) {
                continue;
            }

            $attendances = RetreatActivityAttendance::query()
                ->where('activity_plan_id', $activityPlanId)
                ->whereIn('participant_id', $participantIds)
                ->get()
                ->keyBy('participant_id');

            $counts = [
                'participants' => $participantIds->count(),
                'present' => 0,
                'late' => 0,
                'absent' => 0,
                'excused' => 0,
                'unmarked' => 0,
            ];

            foreach ($participantIds as $participantId) {
                $attendance = $attendances->get($participantId);
                $status = $attendance?->status;

                if (! filled($status)) {
                    $counts['unmarked']++;
                    $totals['unmarked']++;

                    continue;
                }

                if (isset($counts[$status])) {
                    $counts[$status]++;
                    $totals[$status]++;
                }
            }

            $totals['participants'] += $counts['participants'];

            $markedPresent = $counts['present'] + $counts['late'];
            $presentRate = $counts['participants'] > 0
                ? round(($markedPresent / $counts['participants']) * 100, 1)
                : 0.0;

            $rows[] = [
                'atelier_id' => (int) $atelier->id,
                'atelier_numero' => (int) $atelier->numero,
                'age_range' => $placement->describeAtelierAgeRange($atelier),
                'responsable' => $atelier->responsable?->name,
                'participants' => $counts['participants'],
                'present' => $counts['present'],
                'late' => $counts['late'],
                'absent' => $counts['absent'],
                'excused' => $counts['excused'],
                'unmarked' => $counts['unmarked'],
                'present_rate' => $presentRate,
            ];
        }

        $presentEffective = $totals['present'] + $totals['late'];
        $marked = $totals['participants'] - $totals['unmarked'];
        $participants = max(0, $totals['participants']);

        $totals['marked'] = $marked;
        $totals['present_effective'] = $presentEffective;
        $totals['ateliers_count'] = count($rows);
        $totals['present_rate'] = $participants > 0
            ? round(($presentEffective / $participants) * 100, 1)
            : 0.0;
        $totals['pointage_rate'] = $participants > 0
            ? round(($marked / $participants) * 100, 1)
            : 0.0;

        return [
            'activity' => [
                'id' => (int) $plan->id,
                'label' => $this->formatActivityLabel($plan),
            ],
            'totals' => $totals,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<int, string> Activités actives pour le sélecteur
     */
    public function activityOptions(): array
    {
        return RetreatActivityPlan::query()
            ->where('is_active', true)
            ->with('session.event')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(fn (RetreatActivityPlan $plan): array => [
                $plan->id => $this->formatActivityLabel($plan),
            ])
            ->all();
    }

    /**
     * @param RetreatActivityPlan $plan Plan d'activité
     * @return string Libellé affichable
     */
    protected function formatActivityLabel(RetreatActivityPlan $plan): string
    {
        $sessionDate = $plan->session?->start_at?->format('d/m/Y') ?? '—';
        $eventName = $plan->session?->event?->name ?? 'Retraite';

        return "{$plan->title} · {$eventName} · {$sessionDate}";
    }
}
