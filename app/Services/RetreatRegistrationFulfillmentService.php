<?php

namespace App\Services;

use App\Mail\RetreatRegistrationConfirmedMail;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RetreatRegistrationFulfillmentService
{
    public function __construct(
        protected KeccelSmsService $sms,
        protected RetreatPlacementAssignmentService $placementAssignment,
    ) {}

    public function fulfillIfNeeded(RetreatPayment $payment): void
    {
        if ($payment->etat !== 'payee' || ! $payment->access_granted) {
            return;
        }

        DB::transaction(function () use ($payment): void {
            $payment->loadMissing(['participant', 'event']);

            $participant = $payment->participant;
            $event = $payment->event;

            if (! $participant || ! $event) {
                return;
            }

            $this->placementAssignment->assignBalancedPlacements($participant);
            $participant->refresh();
            $participant->load(['chambre', 'atelier']);

            if (! $participant->billet_envoye_email && filled($participant->email)) {
                try {
                    Mail::to($participant->email)->send(
                        new RetreatRegistrationConfirmedMail($participant, $payment, $event)
                    );
                    $participant->update([
                        'billet_envoye_email' => true,
                        'date_billet_envoye' => $participant->date_billet_envoye ?? now(),
                        'billet_envoye' => true,
                    ]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $sent = $this->sendSmsConfirmation($participant, $payment);
            if ($sent) {
                $participant->update(['billet_envoye_whatsapp' => true]);
            }
        });
    }

    protected function sendSmsConfirmation(RetreatParticipant $participant, RetreatPayment $payment): bool
    {
        $body = __('retraite.sms_confirmation_body', [
            'name' => $participant->full_name,
            'ref' => $payment->reference,
        ]);

        if (filled($participant->telephone)) {
            try {
                $this->sms->send((string) $participant->telephone, $body, 'retreat_payment_confirmation');

                return true;
            } catch (\Throwable $e) {
                report($e);
            }

            return false;
        }

        Log::channel('daily')->info('SMS retraite non envoyé', [
            'telephone' => $participant->telephone,
            'message' => $body,
        ]);

        return false;
    }
}
