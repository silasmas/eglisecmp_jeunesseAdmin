<?php

namespace App\Console\Commands;

use App\Models\RetreatActivityPlan;
use App\Services\RetreatActivityAttendanceDeadlineNotifier;
use App\Services\RetreatActivityPlanScheduleService;
use Illuminate\Console\Command;

/**
 * Surveille les échéances de pointage et déclenche les notifications e-mail.
 */
class MonitorRetreatActivityAttendanceDeadlinesCommand extends Command
{
    protected $signature = 'retreat:monitor-activity-attendance-deadlines';

    protected $description = 'Rappels et alertes e-mail pour les fenêtres de pointage des activités';

    /**
     * @return int Code de sortie
     */
    public function handle(
        RetreatActivityPlanScheduleService $scheduleService,
        RetreatActivityAttendanceDeadlineNotifier $notifier,
    ): int {
        $processed = 0;

        RetreatActivityPlan::query()
            ->where('is_active', true)
            ->whereIn('status', ['planned', 'ongoing'])
            ->with('session.event')
            ->each(function (RetreatActivityPlan $plan) use ($scheduleService, $notifier, &$processed): void {
                if (! $scheduleService->isScheduledForToday($plan)) {
                    return;
                }

                if ($scheduleService->isPastDeadline($plan) && blank($plan->attendance_overdue_notified_at)) {
                    $notifier->notifyOverdue($plan);
                    $processed++;

                    return;
                }

                if (
                    $scheduleService->isApproachingDeadline($plan)
                    && blank($plan->attendance_reminder_sent_at)
                ) {
                    $notifier->notifyApproachingDeadline($plan);
                    $processed++;
                }
            });

        $this->info("Notifications pointage traitées : {$processed}.");

        return self::SUCCESS;
    }
}
