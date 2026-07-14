<?php

namespace App\Services;

use App\Mail\RetreatActivityAtelierReportSubmittedMail;
use App\Models\RetreatActivityAtelierReport;
use App\Models\RetreatActivityPlan;
use App\Models\RetreatAtelier;
use App\Models\User;
use App\Support\SuperAdminRecipientResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notifie les administrateurs par e-mail lors de la soumission d'un compte-rendu d'atelier.
 */
class RetreatActivityAtelierReportNotifier
{
    public function __construct(
        protected SuperAdminRecipientResolver $superAdminRecipients,
    ) {}

    /**
     * Envoie l'e-mail de notification aux administrateurs actifs.
     */
    public function notifySubmitted(
        RetreatActivityAtelierReport $report,
        RetreatActivityPlan $activityPlan,
        RetreatAtelier $atelier,
        User $submitter,
    ): void {
        $report->loadMissing(['recorder']);
        $activityPlan->loadMissing(['session.event']);
        $atelier->loadMissing(['responsable', 'adjoint']);

        $recipients = $this->superAdminRecipients->resolveEmailAddressesForRoles(['super_admin', 'panel_user']);

        if ($recipients === []) {
            Log::channel('daily')->info('Compte-rendu atelier : aucun admin e-mail configuré.', [
                'report_id' => $report->id,
                'atelier_id' => $atelier->id,
            ]);

            return;
        }

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(
                    new RetreatActivityAtelierReportSubmittedMail(
                        $report,
                        $activityPlan,
                        $atelier,
                        $submitter->name
                    )
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
