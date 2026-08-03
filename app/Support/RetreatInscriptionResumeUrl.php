<?php

namespace App\Support;

use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;

/**
 * Génère le lien public permettant à un participant de reprendre son inscription au paiement.
 */
class RetreatInscriptionResumeUrl
{
    /** @var list<string> */
    protected const FINAL_PAYMENT_STATES = ['payee', 'remboursee'];

    /**
     * Indique si un participant peut reprendre l'inscription via un lien de paiement.
     *
     * @param RetreatParticipant $participant Participant inscrit
     * @return bool
     */
    public static function canResumeForParticipant(RetreatParticipant $participant): bool
    {
        if ($participant->paiement_valide) {
            return false;
        }

        $payment = self::resolvePayment($participant);

        return $payment !== null && self::canResumeForPayment($payment);
    }

    /**
     * Indique si un paiement permet la génération d'un lien de reprise.
     *
     * @param RetreatPayment $payment Paiement retraite
     * @return bool
     */
    public static function canResumeForPayment(RetreatPayment $payment): bool
    {
        if (in_array($payment->etat, self::FINAL_PAYMENT_STATES, true)) {
            return false;
        }

        if ($payment->participant?->paiement_valide) {
            return false;
        }

        return filled($payment->reference);
    }

    /**
     * URL absolue de reprise pour un participant (étape paiement).
     *
     * @param RetreatParticipant $participant Participant inscrit
     * @return string|null Lien ou null si non applicable
     */
    public static function urlForParticipant(RetreatParticipant $participant): ?string
    {
        if (! self::canResumeForParticipant($participant)) {
            return null;
        }

        $payment = self::resolvePayment($participant);

        return $payment !== null ? self::urlForPayment($payment) : null;
    }

    /**
     * URL absolue de reprise pour un paiement donné.
     *
     * @param RetreatPayment $payment Paiement retraite
     * @return string|null Lien ou null si non applicable
     */
    public static function urlForPayment(RetreatPayment $payment): ?string
    {
        if (! self::canResumeForPayment($payment)) {
            return null;
        }

        return route('retraite.inscription', [
            'resume_payment_ref' => $payment->reference,
        ]);
    }

    /**
     * Dernier paiement actif du participant (le plus récent).
     *
     * @param RetreatParticipant $participant Participant inscrit
     * @return RetreatPayment|null
     */
    public static function resolvePayment(RetreatParticipant $participant): ?RetreatPayment
    {
        if ($participant->relationLoaded('payments')) {
            $payment = $participant->payments->sortByDesc('id')->first();

            return $payment instanceof RetreatPayment ? $payment : null;
        }

        return $participant->payments()->latest('id')->first();
    }
}
