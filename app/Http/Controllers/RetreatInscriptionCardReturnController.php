<?php

namespace App\Http\Controllers;

use App\Models\RetreatPayment;
use App\Services\RetreatInscriptionPaymentCompletionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class RetreatInscriptionCardReturnController extends Controller
{
    public function __construct(
        protected RetreatInscriptionPaymentCompletionService $paymentCompletion
    ) {}

    public function __invoke(string $reference, string $amount, string $currency, string $status): View|RedirectResponse
    {
        unset($amount, $currency);

        $payment = RetreatPayment::query()
            ->with(['participant', 'event'])
            ->where('reference', $reference)
            ->first();

        if (! $payment) {
            return redirect()
                ->route('retraite.inscription')
                ->with([
                    'inscription_card_status' => 'missing',
                    'inscription_payment_ref' => $reference,
                ]);
        }

        if ($status === 'success') {
            $this->paymentCompletion->markElectronicPaid(
                $payment->fresh(),
                'Flux carte FlexPay terminé avec succès.'
            );

            return view('retraite-inscription.card-return-success', [
                'paymentReference' => $payment->reference,
                'participantId' => $payment->participant_id,
            ]);
        }

        if (in_array($status, ['cancel', 'decline', 'failure', 'failed', 'error'], true)) {
            $payment->update([
                'etat' => 'echouee',
                'provider_message' => 'Retour carte FlexPay : '.$status.'.',
            ]);

            return redirect()
                ->route('retraite.inscription')
                ->with([
                    'inscription_card_status' => in_array($status, ['failure', 'failed', 'error'], true) ? 'decline' : $status,
                    'inscription_payment_ref' => $reference,
                ]);
        }

        return redirect()->route('retraite.inscription');
    }
}
