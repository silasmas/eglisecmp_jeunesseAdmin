<?php

namespace App\Observers;

use App\Models\RetreatPayment;
use App\Models\User;
use App\Services\PanelNotificationDispatcher;
use App\Services\RetreatPaymentFailureNotifier;
use App\Services\RetreatRegistrationFulfillmentService;
use App\Support\RetreatParticipantPaymentProof;
use Illuminate\Support\Facades\Auth;

class RetreatPaymentObserver
{
    public function __construct(
        protected PanelNotificationDispatcher $dispatcher,
        protected RetreatRegistrationFulfillmentService $fulfillment,
        protected RetreatPaymentFailureNotifier $paymentFailureNotifier,
    ) {}

    public function updating(RetreatPayment $payment): void
    {
        if ($payment->isDirty('access_granted') && $payment->access_granted && blank($payment->access_granted_by)) {
            $user = Auth::user();
            if ($user instanceof User) {
                $payment->access_granted_by = $user->id;
                $payment->access_granted_at = $payment->access_granted_at ?? now();
            }
        }

        if ($payment->channel === 'cash' && $payment->etat === 'payee' && (float) $payment->amount_paid <= 0) {
            $payment->amount_paid = $payment->amount_expected;
        }
    }

    public function updated(RetreatPayment $payment): void
    {
        if ($payment->wasChanged('etat')) {
            $this->notifyEtatChange($payment);
        }

        if ($payment->wasChanged(['etat', 'access_granted'])) {
            $this->syncParticipantAndFulfill($payment);
        }
    }

    public function created(RetreatPayment $payment): void
    {
        if ($payment->etat === 'payee' && $payment->access_granted) {
            $this->syncParticipantAndFulfill($payment);
        }
    }

    /**
     * @param RetreatPayment $payment Paiement mis à jour
     * @return void
     */
    protected function syncParticipantAndFulfill(RetreatPayment $payment): void
    {
        if ($payment->etat !== 'payee' || ! $payment->access_granted) {
            return;
        }

        $participant = $payment->participant()->first();

        if ($participant && ! $participant->paiement_valide) {
            $participant->update([
                'paiement_valide' => true,
                'registration_status' => 'completed',
                'preuve_paiement' => RetreatParticipantPaymentProof::resolveAfterPayment($participant, $payment),
            ]);
        }

        $this->fulfillment->queueFulfillmentIfNeeded($payment->fresh(['participant', 'event']));
    }

    /**
     * @param RetreatPayment $payment Paiement
     * @return void
     */
    protected function notifyEtatChange(RetreatPayment $payment): void
    {
        $participant = $payment->participant()->first();
        if (! $participant) {
            return;
        }

        if (in_array($payment->etat, ['echouee', 'annulee'], true)) {
            $reason = $payment->etat === 'annulee' ? 'payment_cancelled' : 'payment_failed';
            $message = filled($payment->provider_message)
                ? (string) $payment->provider_message
                : ($payment->etat === 'annulee'
                    ? 'Paiement annulé ou refusé.'
                    : 'Paiement échoué.');

            try {
                $this->paymentFailureNotifier->notify(
                    $payment,
                    $reason,
                    'state_change',
                    $message,
                    [
                        'etat' => $payment->etat,
                        'channel' => $payment->channel,
                    ],
                    $participant,
                );
            } catch (\Throwable $e) {
                report($e);
            }

            return;
        }

        $users = User::query()
            ->role('super_admin')
            ->where('is_active', true)
            ->get()
            ->all();

        $this->dispatcher->notify(
            $users,
            'Paiement FlexPay',
            sprintf(
                'Statut du paiement : %s (réf. %s).',
                $payment->etat,
                $payment->reference
            ),
            null,
            'payment',
            $payment,
        );
    }
}
