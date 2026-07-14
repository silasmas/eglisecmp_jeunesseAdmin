<?php

namespace App\Support;

use App\Models\RetreatParticipant;
use App\Support\RetreatParticipantPaymentProof;

/**
 * Génère l'URL admin sécurisée pour consulter une preuve de paiement.
 */
class RetreatPaymentProofUrl
{
    /**
     * @param RetreatParticipant|null $participant Participant
     * @param bool $download True pour forcer le téléchargement
     * @return string|null URL de consultation ou null
     */
    public static function forParticipant(?RetreatParticipant $participant, bool $download = false): ?string
    {
        if ($participant === null || ! RetreatParticipantPaymentProof::hasViewableProof($participant)) {
            return null;
        }

        $parameters = ['participant' => $participant->id];

        if ($download) {
            $parameters['download'] = 1;
        }

        return route('retreat.admin.payment-proof', $parameters);
    }
}
