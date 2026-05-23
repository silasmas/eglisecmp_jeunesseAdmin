<?php

namespace App\Observers;

use App\Models\RetreatPayment;
use App\Models\User;
use App\Services\PanelNotificationDispatcher;
use App\Services\RetreatRegistrationFulfillmentService;

class RetreatPaymentObserver
{
    public function __construct(
        protected PanelNotificationDispatcher $dispatcher,
        protected RetreatRegistrationFulfillmentService $fulfillment,
    ) {}

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
                'preuve_paiement' => $payment->provider_reference ?? $payment->reference,
            ]);
        }

        $this->fulfillment->fulfillIfNeeded($payment->fresh(['participant', 'event']));
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

        $users = $this->uniqueUsers($this->dispatcher->participantStakeholders($participant));

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

    /**
     * @param  array<int, User|null>  $users
     * @return list<User>
     */
    protected function uniqueUsers(array $users): array
    {
        $out = [];
        $seen = [];
        foreach ($users as $u) {
            if ($u instanceof User && ! isset($seen[$u->id])) {
                $seen[$u->id] = true;
                $out[] = $u;
            }
        }

        return $out;
    }
}
