<?php

namespace App\Mail;

use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail envoyé aux administrateurs lorsqu'un participant soumet une preuve de paiement cash.
 */
class RetreatCashPaymentAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RetreatParticipant $participant,
        public RetreatPayment $payment,
        public ChurchEvent $event
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('retraite.mail_admin_cash_subject', ['event' => $this->event->name]),
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name')
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.retreat-cash-payment-admin',
            with: [
                'participant' => $this->participant,
                'payment' => $this->payment,
                'event' => $this->event,
            ],
        );
    }
}
