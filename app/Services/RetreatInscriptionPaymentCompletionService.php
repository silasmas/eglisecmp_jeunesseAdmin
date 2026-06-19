<?php

namespace App\Services;

use App\Models\RetreatPayment;
use Illuminate\Support\Facades\DB;

/**
 * Marque un paiement inscription comme payé puis déclenche affectations / e-mail / SMS.
 */
class RetreatInscriptionPaymentCompletionService
{
    public function __construct(
        protected RetreatRegistrationFulfillmentService $fulfillment
    ) {}

    public function markElectronicPaid(RetreatPayment $payment, string $providerMessage = ''): void
    {
        $payment->refresh();

        if ($payment->etat === 'payee' && ($payment->participant?->paiement_valide ?? false)) {
            $this->fulfillment->queueFulfillmentIfNeeded($payment->fresh());

            return;
        }

        DB::transaction(function () use ($payment, $providerMessage): void {
            $payment->update([
                'etat' => 'payee',
                'amount_paid' => $payment->amount_expected,
                'access_granted' => true,
                'access_granted_at' => $payment->access_granted_at ?? now(),
                'paid_at' => $payment->paid_at ?? now(),
                'provider_message' => $providerMessage !== '' ? $providerMessage : ($payment->provider_message ?? 'Paiement confirmé.'),
            ]);

            $payment->participant?->update([
                'paiement_valide' => true,
                'registration_status' => 'completed',
                'preuve_paiement' => $payment->provider_reference ?? $payment->reference,
            ]);
        });

        // L'observer planifie l'envoi billet après la réponse HTTP (e-mail ne bloque pas le retour).
    }
}
