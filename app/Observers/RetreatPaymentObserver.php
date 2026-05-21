<?php

namespace App\Observers;

use App\Models\RetreatPayment;
use App\Models\User;
use App\Services\PanelNotificationDispatcher;

class RetreatPaymentObserver
{
    public function __construct(
        protected PanelNotificationDispatcher $dispatcher,
    ) {}

    public function updated(RetreatPayment $payment): void
    {
        if (! $payment->wasChanged('etat')) {
            return;
        }

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
