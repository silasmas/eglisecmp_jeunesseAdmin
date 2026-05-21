<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RetreatParentContactOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public int $expiresInMinutes = 10
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Code OTP e-mail - Vérification parent/tuteur',
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name')
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.retreat-parent-contact-otp',
            with: [
                'otp' => $this->otp,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }
}
