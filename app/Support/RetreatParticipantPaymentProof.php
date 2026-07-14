<?php

namespace App\Support;

use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use Illuminate\Support\Str;

/**
 * Distingue le chemin fichier d'une preuve cash de la référence de paiement.
 */
final class RetreatParticipantPaymentProof
{
    /**
     * @param string|null $value Valeur stockée dans preuve_paiement
     * @return bool True si la valeur pointe vers un fichier consultable
     */
    public static function isStoredFilePath(?string $value): bool
    {
        if (blank($value)) {
            return false;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return true;
        }

        if (Str::startsWith($value, StoragePath::RETREAT_INSCRIPTION_PROOFS.'/')) {
            return true;
        }

        if (preg_match('/^RET-/i', $value) && ! str_contains($value, '/')) {
            return false;
        }

        return str_contains($value, '/');
    }

    /**
     * @param RetreatParticipant|null $participant Participant
     * @return bool True si une preuve fichier peut être affichée
     */
    public static function hasViewableProof(?RetreatParticipant $participant): bool
    {
        return self::isStoredFilePath($participant?->preuve_paiement);
    }

    /**
     * Conserve le fichier de preuve cash après validation ; sinon enregistre la référence paiement.
     *
     * @param RetreatParticipant $participant Participant
     * @param RetreatPayment $payment Paiement validé
     * @return string|null Valeur à persister dans preuve_paiement
     */
    public static function resolveAfterPayment(RetreatParticipant $participant, RetreatPayment $payment): ?string
    {
        if (self::isStoredFilePath($participant->preuve_paiement)) {
            return $participant->preuve_paiement;
        }

        return $payment->provider_reference ?? $payment->reference;
    }
}
