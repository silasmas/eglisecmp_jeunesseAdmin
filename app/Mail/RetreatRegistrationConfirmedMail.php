<?php

namespace App\Mail;

use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Support\RetreatMailUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RetreatRegistrationConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RetreatParticipant $participant,
        public RetreatPayment $payment,
        public ChurchEvent $event,
        public ?string $billetUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('retraite.mail_registration_subject', ['event' => $this->event->name]),
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name')
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.retreat-registration-confirmed',
            with: [
                'participant' => $this->participant,
                'payment' => $this->payment,
                'event' => $this->event,
                'billetUrl' => $this->billetUrl ?? RetreatMailUrl::route('retraite.inscription.billet', [
                    'token' => $this->participant->download_token,
                ]),
                'portalUrl' => RetreatMailUrl::portal(),
            ],
        );
    }
}
