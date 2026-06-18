<?php

namespace App\Support;

use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;

/**
 * Détermine la vue billet du portail inscription selon l'état de paiement.
 */
class RetreatRegistrationBadgeViewResolver
{
    /**
     * Paiement prioritaire pour l'affichage (encaissé, sinon le plus récent).
     *
     * @param RetreatParticipant $participant Participant inscrit
     * @return RetreatPayment|null Paiement retenu
     */
    public static function resolvePrimaryPayment(RetreatParticipant $participant): ?RetreatPayment
    {
        $participant->loadMissing(['payments']);

        return $participant->payments
            ->first(fn (RetreatPayment $payment): bool => $payment->etat === 'payee')
            ?? $participant->payments->sortByDesc('updated_at')->first();
    }

    /**
     * Vue billet pour le portail public (electronic_success, sponsorship_success, cash_pending…).
     *
     * @param RetreatParticipant|null $participant Participant inscrit
     * @param RetreatPayment|null $payment Paiement associé
     * @return string Identifiant de vue
     */
    public static function resolve(?RetreatParticipant $participant, ?RetreatPayment $payment): string
    {
        if (! $participant) {
            return 'unknown';
        }

        $channel = $payment?->channel;

        if ($participant->paiement_valide && $channel === 'sponsorship_voucher') {
            return 'sponsorship_success';
        }

        if ($participant->paiement_valide && in_array($channel, ['mobile_money', 'card'], true)) {
            return 'electronic_success';
        }

        if ($participant->paiement_valide && ! $payment) {
            return 'electronic_success';
        }

        if ($channel === 'cash') {
            return $participant->paiement_valide ? 'cash_validated' : 'cash_pending';
        }

        if ($participant->paiement_valide && $payment && $payment->etat === 'payee') {
            return 'electronic_success';
        }

        return 'payment_incomplete';
    }
}
