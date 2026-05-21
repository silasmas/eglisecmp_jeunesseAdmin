<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ParticipantAssignmentMailNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Bonjour,')
            ->line($this->message)
            ->line("Cette operation d'affectation a bien ete executee dans le systeme.")
            ->salutation('Equipe CMP Jeunesse');
    }
}
