<?php

namespace App\Mail;

use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Models\RetreatPaymentFailureAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
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

        return new Envelope(
            subject: __('retraite.mail_payment_failure_subject', [
                'event' => $eventName,
                'reference' => $this->alert->reference,
            ]),
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name')
            ),
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
