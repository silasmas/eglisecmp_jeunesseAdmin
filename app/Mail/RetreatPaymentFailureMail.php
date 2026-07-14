<?php

namespace App\Mail;

use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Models\RetreatPaymentFailureAlert;
use App\Support\CmpMailEnvelope;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail envoyé lors d'un échec de paiement d'inscription retraite.
 */
class RetreatPaymentFailureMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param RetreatPaymentFailureAlert $alert Alerte enregistrée
     * @param RetreatParticipant|null $participant Participant concerné
     * @param RetreatPayment|null $payment Paiement concerné
     */
    public function __construct(
        public RetreatPaymentFailureAlert $alert,
        public ?RetreatParticipant $participant,
        public ?RetreatPayment $payment,
    ) {}

    /**
     * @return Envelope Enveloppe de l'e-mail
     */
    public function envelope(): Envelope
    {
        $eventName = $this->payment?->event?->name
            ?? $this->participant?->event?->name
            ?? 'Retraite';

        return CmpMailEnvelope::make(
            __('retraite.mail_payment_failure_subject', [
                'event' => $eventName,
                'reference' => $this->alert->reference,
            ])
        );
    }

    /**
     * @return Content Corps de l'e-mail
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.retreat-payment-failure',
            with: [
                'alert' => $this->alert,
                'participant' => $this->participant,
                'payment' => $this->payment,
            ],
        );
    }
}
