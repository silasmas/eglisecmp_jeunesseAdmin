<?php

namespace App\Services;

use App\Mail\RetreatActivityAttendanceDeadlineOverdueMail;
use App\Mail\RetreatActivityAttendanceDeadlineReminderMail;
use App\Models\RetreatActivityPlan;
use App\Models\RetreatAtelier;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie les rappels et alertes de fin de fenêtre de pointage activité.
 */
class RetreatActivityAttendanceDeadlineNotifier
{
    public function __construct(
        protected RetreatActivityPlanScheduleService $scheduleService,
    ) {}

    /**
     * Rappel aux responsables et adjoints d'atelier (~5 min avant la fin).
     *
     * @param RetreatActivityPlan $plan Activité concernée
     * @return void
     */
    public function notifyApproachingDeadline(RetreatActivityPlan $plan): void
    {
        $plan->loadMissing(['session.event']);
        $deadline = $this->scheduleService->resolveAttendanceDeadline($plan);

        if (! $deadline) {
            return;
        }

        $recipients = $this->atelierLeadRecipients();

        if ($recipients->isEmpty()) {
            Log::channel('daily')->info('Rappel pointage activité : aucun encadreur atelier avec e-mail.', [
                'activity_plan_id' => $plan->id,
            ]);

            return;
        }

        foreach ($recipients as $user) {
            try {
                Mail::to($user->email)->send(
                    new RetreatActivityAttendanceDeadlineReminderMail($plan, $deadline)
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $plan->update(['attendance_reminder_sent_at' => now()]);
    }

    /**
     * Alerte aux administrateurs si la fenêtre est dépassée.
     *
     * @param RetreatActivityPlan $plan Activité concernée
     * @return void
     */
    public function notifyOverdue(RetreatActivityPlan $plan): void
    {
        $plan->loadMissing(['session.event']);
        $deadline = $this->scheduleService->resolveAttendanceDeadline($plan);

        if (! $deadline) {
            return;
        }

        $recipients = User::query()
            ->role(['super_admin', 'panel_user'])
            ->where('is_active', true)
            ->whereNotNull('email')
            ->get();

        if ($recipients->isEmpty()) {
            Log::channel('daily')->info('Pointage activité en retard : aucun admin e-mail.', [
                'activity_plan_id' => $plan->id,
            ]);

            return;
        }

        foreach ($recipients as $admin) {
            try {
                Mail::to($admin->email)->send(
                    new RetreatActivityAttendanceDeadlineOverdueMail($plan, $deadline)
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $plan->update(['attendance_overdue_notified_at' => now()]);
    }

    /**
     * @return Collection<int, User>
     */
    private function atelierLeadRecipients(): Collection
    {
        $ateliers = RetreatAtelier::query()
            ->where('is_active', true)
            ->with(['responsable', 'adjoint'])
            ->get();

        return $ateliers
            ->flatMap(fn (RetreatAtelier $atelier): array => [
                $atelier->responsable,
                $atelier->adjoint,
            ])
            ->filter(fn (?User $user): bool => $user instanceof User && $user->is_active && filled($user->email))
            ->unique('id')
            ->values();
    }
}
