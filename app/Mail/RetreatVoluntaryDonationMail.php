<?php

namespace App\Mail;

use App\Filament\Resources\RetreatVoluntaryDonations\RetreatVoluntaryDonationResource;
use App\Models\RetreatVoluntaryDonation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail aux super_admin lors d'un don volontaire retraite.
 */
class RetreatVoluntaryDonationMail extends Mailable
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

        $subject = $this->donation->status === RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED
            ? "Don cash à valider — {$eventName}"
            : "Nouveau don volontaire — {$eventName}";

        return new Envelope(
            subject: $subject,
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name')
            ),
        );
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        $adminDonationUrl = null;

        try {
            $adminDonationUrl = RetreatVoluntaryDonationResource::getUrl(
                'view',
                ['record' => $this->donation->id],
                panel: 'admin',
            );
        } catch (\Throwable) {
            $adminDonationUrl = null;
        }

        return new Content(
            markdown: 'emails.retreat-voluntary-donation',
            with: [
                'donation' => $this->donation,
                'adminDonationUrl' => $adminDonationUrl,
            ],
        );
    }
}
