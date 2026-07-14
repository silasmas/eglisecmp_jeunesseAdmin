<?php

namespace App\Mail;

use App\Models\RetreatVoluntaryDonation;
use App\Support\CmpMailEnvelope;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail de confirmation envoyé au donateur après enregistrement ou paiement.
 */
class RetreatVoluntaryDonationDonorMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param RetreatVoluntaryDonation $donation Don concerné
     */
    public function __construct(
        public RetreatVoluntaryDonation $donation,
    ) {}

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        $eventName = $this->donation->event?->name ?? 'Retraite';

        $subject = match (true) {
            $this->donation->status === RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED => "Preuve reçue — validation en cours — {$eventName}",
            $this->donation->cash_purpose === RetreatVoluntaryDonation::PURPOSE_SPONSOR_YOUTH
                && $this->donation->status === RetreatVoluntaryDonation::STATUS_PAID => "Paiement confirmé — contactez l'administration — {$eventName}",
            default => "Confirmation de votre don — {$eventName}",
        };

        return CmpMailEnvelope::make($subject);
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.retreat-voluntary-donation-donor',
            with: [
                'donation' => $this->donation,
            ],
        );
    }
}
