<?php

namespace App\Support;

use App\Models\RetreatParticipant;

/**
 * Génère l'URL admin sécurisée pour consulter une preuve de paiement.
 */
class RetreatPaymentProofUrl
{
    /**
     * @param RetreatParticipant|null $participant Participant
     * @return string|null URL de consultation ou null
     */
    public static function forParticipant(?RetreatParticipant $participant): ?string
    {
        if ($participant === null || blank($participant->preuve_paiement)) {
            return null;
        }

        return route('retreat.admin.payment-proof', ['participant' => $participant->id]);
    }
}
