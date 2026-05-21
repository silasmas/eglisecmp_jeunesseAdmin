<?php

namespace App\Filament\Resources\RetreatSessions\Widgets;

use App\Models\RetreatActivityAttendance;
use App\Models\RetreatSession;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RetreatSessionsStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $now = now();

        $participantStats = RetreatSession::query()
            ->orderBy('start_at')
            ->get()
            ->map(function (RetreatSession $session): Stat {
                $count = RetreatActivityAttendance::query()
                    ->whereHas('activityPlan', fn ($query) => $query->where('session_id', $session->id))
                    ->distinct('participant_id')
                    ->count('participant_id');

                return Stat::make("Participants - {$session->title}", (string) $count);
            })
            ->all();

        return [
            Stat::make('Nombre de sessions', (string) RetreatSession::query()->count()),
            Stat::make('Sessions passees', (string) RetreatSession::query()->where('end_at', '<', $now)->count()),
            Stat::make('Sessions en cours', (string) RetreatSession::query()->where('start_at', '<=', $now)->where('end_at', '>=', $now)->count()),
            Stat::make('Sessions futures', (string) RetreatSession::query()->where('start_at', '>', $now)->count()),
            ...$participantStats,
        ];
    }
}
