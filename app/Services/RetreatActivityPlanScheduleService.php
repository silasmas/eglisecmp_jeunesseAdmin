<?php

namespace App\Services;

use App\Models\RetreatActivityPlan;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Calcule les échéances de pointage pour une activité planifiée.
 */
class RetreatActivityPlanScheduleService
{
    /**
     * @param RetreatActivityPlan $plan Activité planifiée
     * @return CarbonInterface|null Fin de la fenêtre de pointage
     */
    public function resolveAttendanceDeadline(RetreatActivityPlan $plan): ?CarbonInterface
    {
        $plan->loadMissing('session');

        $session = $plan->session;
        if (! $session?->start_at || blank($plan->starts_at)) {
            return null;
        }

        $date = $session->start_at->format('Y-m-d');
        $startTime = $plan->starts_at instanceof CarbonInterface
            ? $plan->starts_at->format('H:i:s')
            : (string) $plan->starts_at;

        $activityStart = Carbon::parse("{$date} {$startTime}");
        $windowMinutes = max(1, (int) ($plan->attendance_window_minutes ?? 30));

        return $activityStart->copy()->addMinutes($windowMinutes);
    }

    /**
     * @param RetreatActivityPlan $plan Activité
     * @return bool Vrai si le pointage est encore autorisé
     */
    public function isAttendanceWindowOpen(RetreatActivityPlan $plan): bool
    {
        $deadline = $this->resolveAttendanceDeadline($plan);

        if (! $deadline) {
            return true;
        }

        return now()->lte($deadline);
    }

    /**
     * @param RetreatActivityPlan $plan Activité
     * @return bool Vrai si la fin de fenêtre est dans les 5 prochaines minutes
     */
    public function isApproachingDeadline(RetreatActivityPlan $plan): bool
    {
        $deadline = $this->resolveAttendanceDeadline($plan);

        if (! $deadline) {
            return false;
        }

        $now = now();

        return $now->lt($deadline) && $now->gte($deadline->copy()->subMinutes(5));
    }

    /**
     * @param RetreatActivityPlan $plan Activité
     * @return bool Vrai si la fenêtre de pointage est dépassée
     */
    public function isPastDeadline(RetreatActivityPlan $plan): bool
    {
        $deadline = $this->resolveAttendanceDeadline($plan);

        return $deadline !== null && now()->gt($deadline);
    }

    /**
     * @param RetreatActivityPlan $plan Activité
     * @return bool L'activité concerne la journée en cours (session)
     */
    public function isScheduledForToday(RetreatActivityPlan $plan): bool
    {
        $plan->loadMissing('session');

        if (! $plan->session?->start_at) {
            return false;
        }

        return $plan->session->start_at->isSameDay(now());
    }
}
