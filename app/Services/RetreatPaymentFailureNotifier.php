<?php

namespace App\Services;

use App\Filament\Pages\RetreatPaymentFailureMonitor;
use App\Mail\RetreatPaymentFailureMail;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Models\RetreatPaymentFailureAlert;
use App\Models\User;
use App\Support\RetreatPaymentFailureAlertsSchema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Enregistre, affiche et notifie par e-mail les échecs de paiement d'inscription.
 */
class RetreatPaymentFailureNotifier
{
    public function __construct(
        protected PanelNotificationDispatcher $panelNotifications,
    ) {}

    /**
     * Enregistre une alerte, envoie l'e-mail configuré et notifie les super_admin.
     *
     * @param RetreatPayment|null $payment Paiement concerné
     * @param string $reason Code court de la cause (ex. mobile_init_failed)
     * @param string $source Origine technique (ex. mobile_init)
     * @param string $message Message lisible pour l'administrateur
     * @param array<string, mixed>|null $technicalDetail Détails bruts (API, HTTP, etc.)
     * @param RetreatParticipant|null $participant Participant si le paiement est absent
     * @return RetreatPaymentFailureAlert|null Alerte créée ou null si doublon récent
     */
    public function notify(
        ?RetreatPayment $payment,
        string $reason,
        string $source,
        string $message,
        ?array $technicalDetail = null,
        ?RetreatParticipant $participant = null,
    ): ?RetreatPaymentFailureAlert {
        $payment?->loadMissing(['participant.event', 'event']);
        $participant = $participant ?? $payment?->participant;
        $participant?->loadMissing(['event']);

        $reference = $payment?->reference ?? 'N/A';
        $dedupeKey = sprintf(
            'retreat_payment_failure:%s:%s:%s',
            $payment?->id ?? 'none',
            $reason,
            $source,
        );

        if (! Cache::add($dedupeKey, true, now()->addMinutes(10))) {
            return null;
        }

        if (! RetreatPaymentFailureAlertsSchema::isReady()) {
            $this->notifyWithoutPersistence($payment, $participant, $reason, $source, $message, $reference);

            return null;
        }

        $alert = RetreatPaymentFailureAlert::query()->create([
            'retreat_payment_id' => $payment?->id,
            'participant_id' => $participant?->id ?? $payment?->participant_id,
            'event_id' => $payment?->event_id ?? $participant?->event_id,
            'reference' => $reference,
            'channel' => $payment?->channel,
            'failure_reason' => $reason,
            'failure_source' => $source,
            'message' => $message,
            'technical_detail' => $technicalDetail,
        ]);

        $this->notifySuperAdmins($alert, $participant, $payment);
        $this->sendConfiguredEmail($alert, $participant, $payment);

        return $alert;
    }

    /**
     * Envoie e-mail et notification cloche même si la table SQL n'est pas encore migrée.
     *
     * @param RetreatPayment|null $payment Paiement concerné
     * @param RetreatParticipant|null $participant Participant lié
     * @param string $reason Code de la cause
     * @param string $source Origine technique
     * @param string $message Message lisible
     * @param string $reference Référence paiement
     * @return void
     */
    protected function notifyWithoutPersistence(
        ?RetreatPayment $payment,
        ?RetreatParticipant $participant,
        string $reason,
        string $source,
        string $message,
        string $reference,
    ): void {
        $alert = new RetreatPaymentFailureAlert([
            'reference' => $reference,
            'channel' => $payment?->channel,
            'failure_reason' => $reason,
            'failure_source' => $source,
            'message' => $message,
            'created_at' => now(),
        ]);

        $this->notifySuperAdmins($alert, $participant, $payment);
        $this->sendConfiguredEmail($alert, $participant, $payment, persistSentAt: false);
    }

    /**
     * @param RetreatPaymentFailureAlert $alert Alerte enregistrée
     * @param RetreatParticipant|null $participant Participant lié
     * @param RetreatPayment|null $payment Paiement lié
     * @return void
     */
    protected function notifySuperAdmins(
        RetreatPaymentFailureAlert $alert,
        ?RetreatParticipant $participant,
        ?RetreatPayment $payment,
    ): void {
        $admins = User::query()
            ->role('super_admin')
            ->where('is_active', true)
            ->get()
            ->all();

        if ($admins === []) {
            return;
        }

        $participantName = $participant?->full_name ?? 'Participant inconnu';
        $link = $this->resolveMonitorUrl();

        $this->panelNotifications->notify(
            $admins,
            'Échec paiement inscription',
            sprintf('%s — réf. %s', $participantName, $alert->reference),
            $link,
            'payment',
            $payment ?? $participant,
        );
    }

    /**
     * @param RetreatPaymentFailureAlert $alert Alerte enregistrée
     * @param RetreatParticipant|null $participant Participant lié
     * @param RetreatPayment|null $payment Paiement lié
     * @return void
     */
    protected function sendConfiguredEmail(
        RetreatPaymentFailureAlert $alert,
        ?RetreatParticipant $participant,
        ?RetreatPayment $payment,
        bool $persistSentAt = true,
    ): void {
        $recipient = (string) config('retraite.payment_failure_notify_email', '');

        if (! filled($recipient)) {
            return;
        }

        try {
            Mail::to($recipient)->send(
                new RetreatPaymentFailureMail($alert, $participant, $payment)
            );

            if ($persistSentAt && $alert->exists) {
                $alert->update([
                    'email_sent_at' => now(),
                    'email_recipient' => $recipient,
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @return string|null URL de la page de surveillance des échecs
     */
    protected function resolveMonitorUrl(): ?string
    {
        try {
            return RetreatPaymentFailureMonitor::getUrl();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
