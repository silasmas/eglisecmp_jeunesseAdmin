<?php

namespace App\Services;

use App\Enums\EventAccessOtpChannel;
use App\Mail\RetreatRegistrationConfirmedMail;
use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Après paiement validé : affectations, puis envoi billet (e-mail ou SMS selon l'événement).
 */
class RetreatRegistrationFulfillmentService
{
    public function __construct(
        protected KeccelSmsService $sms,
        protected RetreatPlacementAssignmentService $placementAssignment,
    ) {}

    /**
     * @param RetreatPayment $payment Paiement confirmé avec accès accordé
     * @return void
     */
    public function fulfillIfNeeded(RetreatPayment $payment): void
    {
        if ($payment->etat !== 'payee' || ! $payment->access_granted) {
            return;
        }

        DB::transaction(function () use ($payment): void {
            $payment->loadMissing(['participant', 'event']);

            $participant = $payment->participant;
            $event = $payment->event;

            if (! $participant || ! $event || blank($participant->download_token)) {
                return;
            }

            $this->placementAssignment->assignBalancedPlacements($participant);
            $participant->refresh();
            $participant->load(['chambre', 'atelier']);

            $this->sendBilletNotification($participant, $payment, false);
        });
    }

    /**
     * Envoie le billet selon le canal de l'événement (avec option de renvoi).
     *
     * @param RetreatParticipant $participant Participant
     * @param RetreatPayment $payment Paiement lié
     * @param bool $forceResend Renvoyer même si déjà marqué envoyé
     * @return array{success: bool, message: string, channel: string|null}
     */
    public function sendBilletNotification(
        RetreatParticipant $participant,
        RetreatPayment $payment,
        bool $forceResend = false,
    ): array {
        $participant->loadMissing(['event']);
        $payment->loadMissing(['event']);
        $event = $payment->event ?? $participant->event;

        if (! $event || blank($participant->download_token)) {
            return [
                'success' => false,
                'message' => 'Données insuffisantes pour envoyer le billet.',
                'channel' => null,
            ];
        }

        $billetUrl = route('retraite.inscription.billet', [
            'token' => $participant->download_token,
        ], absolute: true);

        $channel = $this->resolveConfirmationChannel($event, $participant);

        if ($channel === 'sms') {
            $sent = $this->sendBilletSms($participant, $payment, $billetUrl, $forceResend);
        } else {
            $sent = $this->sendBilletEmail($participant, $payment, $event, $billetUrl, $forceResend);
        }

        if (! $sent) {
            return [
                'success' => false,
                'message' => $channel === 'sms'
                    ? 'Impossible d\'envoyer le SMS (téléphone manquant ou erreur d\'envoi).'
                    : 'Impossible d\'envoyer l\'e-mail (adresse manquante ou erreur d\'envoi).',
                'channel' => $channel,
            ];
        }

        return [
            'success' => true,
            'message' => $channel === 'sms'
                ? 'Billet envoyé par SMS.'
                : 'Billet envoyé par e-mail.',
            'channel' => $channel,
        ];
    }

    /**
     * Canal de confirmation configuré sur l'événement, avec repli e-mail puis SMS.
     *
     * @param ChurchEvent $event Événement
     * @param RetreatParticipant $participant Participant
     * @return string sms|email
     */
    protected function resolveConfirmationChannel(ChurchEvent $event, RetreatParticipant $participant): string
    {
        $configured = $event->access_otp_channel instanceof EventAccessOtpChannel
            ? $event->access_otp_channel
            : EventAccessOtpChannel::tryFrom((string) $event->access_otp_channel);

        if ($configured === EventAccessOtpChannel::Sms && filled($participant->telephone)) {
            return 'sms';
        }

        if ($configured === EventAccessOtpChannel::Email && filled($participant->email)) {
            return 'email';
        }

        if (filled($participant->email)) {
            return 'email';
        }

        if (filled($participant->telephone)) {
            return 'sms';
        }

        return 'email';
    }

    /**
     * @param RetreatParticipant $participant Participant
     * @param RetreatPayment $payment Paiement
     * @param ChurchEvent $event Événement
     * @param string $billetUrl Lien billet public
     * @param bool $forceResend Forcer le renvoi
     * @return bool Succès de l'envoi
     */
    protected function sendBilletEmail(
        RetreatParticipant $participant,
        RetreatPayment $payment,
        ChurchEvent $event,
        string $billetUrl,
        bool $forceResend = false,
    ): bool {
        if (blank($participant->email)) {
            return false;
        }

        if (! $forceResend && $participant->billet_envoye_email) {
            return false;
        }

        try {
            Mail::to($participant->email)->send(
                new RetreatRegistrationConfirmedMail($participant, $payment, $event, $billetUrl)
            );

            $participant->update([
                'billet_envoye_email' => true,
                'date_billet_envoye' => now(),
                'billet_envoye' => true,
            ]);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * @param RetreatParticipant $participant Participant
     * @param RetreatPayment $payment Paiement
     * @param string $billetUrl Lien billet public
     * @param bool $forceResend Forcer le renvoi
     * @return bool Succès de l'envoi
     */
    protected function sendBilletSms(
        RetreatParticipant $participant,
        RetreatPayment $payment,
        string $billetUrl,
        bool $forceResend = false,
    ): bool {
        if (blank($participant->telephone)) {
            return false;
        }

        if (! $forceResend && $participant->billet_envoye_whatsapp) {
            return false;
        }

        $body = __('retraite.sms_confirmation_body', [
            'name' => $participant->prenom ?: $participant->full_name,
            'ref' => $payment->reference,
            'billet_url' => $billetUrl,
        ]);

        try {
            $this->sms->send((string) $participant->telephone, $body, 'retreat_payment_confirmation');

            $participant->update([
                'billet_envoye_whatsapp' => true,
                'date_billet_envoye' => now(),
                'billet_envoye' => true,
            ]);

            return true;
        } catch (\Throwable $e) {
            report($e);

            Log::channel('daily')->info('SMS billet retraite non envoyé', [
                'participant_id' => $participant->id,
                'telephone' => $participant->telephone,
            ]);

            return false;
        }
    }
}
