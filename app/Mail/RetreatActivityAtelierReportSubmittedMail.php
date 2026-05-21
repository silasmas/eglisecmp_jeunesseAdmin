<?php

namespace App\Mail;

use App\Models\RetreatActivityAtelierReport;
use App\Models\RetreatActivityPlan;
use App\Models\RetreatAtelier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail envoyé aux administrateurs lors de la soumission d'un compte-rendu d'atelier.
 */
class RetreatActivityAtelierReportSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RetreatActivityAtelierReport $report,
        public RetreatActivityPlan $activityPlan,
        public RetreatAtelier $atelier,
        public string $submitterName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('retraite.mail_atelier_report_subject', [
                'atelier' => $this->atelier->numero,
                'activity' => $this->activityPlan->title,
            ]),
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name')
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.retreat-activity-atelier-report-submitted',
            with: [
                'report' => $this->report,
                'activityPlan' => $this->activityPlan,
                'atelier' => $this->atelier,
                'submitterName' => $this->submitterName,
            ],
        );
    }
}
