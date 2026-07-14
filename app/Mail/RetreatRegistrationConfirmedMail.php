<?php

namespace App\Mail;

use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Support\RetreatMailUrl;
use App\Support\ChurchEventParticipantDocuments;
use App\Support\RetreatPlacementVisibility;
use App\Support\CmpMailEnvelope;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
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
        return CmpMailEnvelope::make(
            __('retraite.mail_registration_subject', ['event' => $this->event->name])
        );
    }

    public function content(): Content
    {
        $showPlacements = RetreatPlacementVisibility::shouldReveal($this->participant);
        $hasDocuments = ChurchEventParticipantDocuments::hasAny($this->event);

        return new Content(
            markdown: 'emails.retreat-registration-confirmed',
            with: [
                'participant' => $this->participant,
                'payment' => $this->payment,
                'event' => $this->event,
                'showPlacements' => $showPlacements,
                'placementsPendingMessage' => $showPlacements ? null : RetreatPlacementVisibility::pendingMessage($this->participant),
                'hasParticipantDocuments' => $hasDocuments,
                'billetUrl' => $this->billetUrl ?? RetreatMailUrl::route('retraite.inscription.billet', [
                    'token' => $this->participant->download_token,
                ]),
                'portalUrl' => RetreatMailUrl::portal(),
            ],
        );
    }
}
