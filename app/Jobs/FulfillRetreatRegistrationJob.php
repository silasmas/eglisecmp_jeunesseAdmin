<?php

namespace App\Jobs;

use App\Models\RetreatPayment;
use App\Services\RetreatRegistrationFulfillmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Affectations et envoi billet après paiement — exécuté hors requête HTTP pour ne pas bloquer la confirmation.
 */
class FulfillRetreatRegistrationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param int $paymentId Identifiant du paiement confirmé
     */
    public function __construct(public int $paymentId) {}

    /**
     * @param RetreatRegistrationFulfillmentService $fulfillment Service d'envoi billet
     * @return void
     */
    public function handle(RetreatRegistrationFulfillmentService $fulfillment): void
    {
        $payment = RetreatPayment::query()
            ->with(['participant', 'event'])
            ->find($this->paymentId);

        if (! $payment || $payment->etat !== 'payee' || ! $payment->access_granted) {
            return;
        }

        try {
            $fulfillment->fulfillIfNeeded($payment);
        } catch (\Throwable $e) {
            report($e);
            Log::channel('daily')->warning('Fulfillment retraite en arrière-plan échoué', [
                'payment_id' => $this->paymentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
