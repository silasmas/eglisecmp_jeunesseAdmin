<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification e-mail d'affectation de masse des participants (chambre / atelier).
 */
class ParticipantAssignmentMailNotification extends Notification
{
    use Queueable;

    /**
     * @param string $title Sujet de l'e-mail
     * @param string $message Corps du message
     */
    public function __construct(
        protected string $title,
        protected string $message,
    ) {}

    /**
     * @param object $notifiable Destinataire
     * @return array<int, string> Canaux de notification
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param object $notifiable Destinataire
     * @return MailMessage Message e-mail brandé CMP
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->markdown('emails.participant-assignment', [
                'title' => $this->title,
                'message' => $this->message,
            ]);
    }
}
