<?php

namespace App\Filament\Resources\RetreatActivityAttendances\Widgets;

use App\Models\RetreatActivityAttendance;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RetreatActivityAttendancesStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            ...$this->dayStats(),
            ...$this->activityStats(),
            ...$this->statusStats(),
            ...$this->genderStats(),
        ];
    }

    private function statusStats(): array
    {
        $labels = [
            'present' => 'Presents',
            'absent' => 'Absents',
            'late' => 'En retard',
            'excused' => 'Excuses',
        ];

        return collect($labels)
            ->map(fn (string $label, string $status): Stat => Stat::make(
                $label,
                (string) RetreatActivityAttendance::query()->where('status', $status)->count()
            ))
            ->values()
            ->all();
    }

    private function dayStats(): array
    {
        return RetreatActivityAttendance::query()
            ->join('retreat_activity_plans', 'retreat_activity_attendances.activity_plan_id', '=', 'retreat_activity_plans.id')
            ->join('retreat_session', 'retreat_activity_plans.session_id', '=', 'retreat_session.id')
            ->selectRaw('DATE(retreat_session.start_at) as attendance_day, count(*) as aggregate')
            ->groupBy('attendance_day')
            ->orderBy('attendance_day')
            ->pluck('aggregate', 'attendance_day')
            ->map(fn ($count, $day): Stat => Stat::make(
                'Jour '.$this->formatDay((string) $day),
                (string) $count
            )->description($this->statusDescription(
                RetreatActivityAttendance::query()
                    ->whereHas('activityPlan.session', fn (Builder $query): Builder => $query->whereDate('start_at', $day))
            )))
            ->values()
            ->all();
    }

    private function activityStats(): array
    {
        return RetreatActivityAttendance::query()
            ->join('retreat_activity_plans', 'retreat_activity_attendances.activity_plan_id', '=', 'retreat_activity_plans.id')
            ->selectRaw('retreat_activity_plans.title as activity_title, count(*) as aggregate')
            ->groupBy('activity_title')
            ->orderBy('activity_title')
            ->pluck('aggregate', 'activity_title')
            ->map(fn ($count, $activity): Stat => Stat::make(
                "Activite {$activity}",
                (string) $count
            )->description($this->statusDescription(
                RetreatActivityAttendance::query()
                    ->whereHas('activityPlan', fn (Builder $query): Builder => $query->where('title', $activity))
            )))
            ->values()
            ->all();
    }

    private function genderStats(): array
    {
        return RetreatActivityAttendance::query()
            ->join('retreat_participant', 'retreat_activity_attendances.participant_id', '=', 'retreat_participant.id')
            ->selectRaw("COALESCE(retreat_participant.sexe, 'Non defini') as sexe, count(*) as aggregate")
            ->groupBy('sexe')
            ->orderBy('sexe')
            ->pluck('aggregate', 'sexe')
            ->map(fn ($count, $sexe): Stat => Stat::make('Genre '.ucfirst((string) $sexe), (string) $count))
            ->values()
            ->all();
    }

    private function statusDescription(Builder $query): string
    {
        $counts = (clone $query)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return sprintf(
            'Presents: %s | Absents: %s | Retards: %s | Excuses: %s',
            $counts->get('present', 0),
            $counts->get('absent', 0),
            $counts->get('late', 0),
            $counts->get('excused', 0)
        );
    }

    private function formatDay(string $day): string
    {
        return Carbon::parse($day)->locale('fr')->isoFormat('DD MMM YYYY');
    }
}
