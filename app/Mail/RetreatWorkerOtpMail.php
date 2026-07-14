<?php

namespace App\Mail;

use App\Support\CmpMailEnvelope;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RetreatWorkerOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public int $expiresInMinutes = 10
    ) {}

    public function envelope(): Envelope
    {
        return CmpMailEnvelope::make(__('retraite.mail_otp_worker_subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.retreat-worker-otp',
            with: [
                'otp' => $this->otp,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }
}
