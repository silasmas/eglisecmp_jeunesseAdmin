<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Vérifie la présence de la table des alertes d'échec de paiement.
 */
class RetreatPaymentFailureAlertsSchema
{
    /**
     * @return bool La table retreat_payment_failure_alerts existe en base
     */
    public static function isReady(): bool
    {
        return Schema::hasTable('retreat_payment_failure_alerts');
    }
}
