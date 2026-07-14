<?php

namespace App\Mail;

use App\Models\RetreatActivityPlan;
use App\Support\CmpMailEnvelope;
use App\Support\RetreatMailUrl;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Rappel aux responsables d'atelier : la fenêtre de pointage se termine bientôt.
 */
class RetreatActivityAttendanceDeadlineReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RetreatActivityPlan $activityPlan,
        public CarbonInterface $deadline,
    ) {}

    public function envelope(): Envelope
    {
        return CmpMailEnvelope::make(
            __('retraite.mail_attendance_reminder_subject', [
                'activity' => $this->activityPlan->title,
            ])
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.retreat-activity-attendance-deadline-reminder',
            with: [
                'activityPlan' => $this->activityPlan,
                'deadline' => $this->deadline,
                'portalUrl' => RetreatMailUrl::portal(),
            ],
        );
    }
}
