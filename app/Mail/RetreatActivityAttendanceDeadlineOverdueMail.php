<?php

namespace App\Mail;

use App\Models\RetreatActivityPlan;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Alerte administrateur : la fenêtre de pointage d'une activité est dépassée.
 */
class RetreatActivityAttendanceDeadlineOverdueMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RetreatActivityPlan $activityPlan,
        public CarbonInterface $deadline,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('retraite.mail_attendance_overdue_subject', [
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
            markdown: 'emails.retreat-activity-attendance-deadline-overdue',
            with: [
                'activityPlan' => $this->activityPlan,
                'deadline' => $this->deadline,
            ],
        );
    }
}
