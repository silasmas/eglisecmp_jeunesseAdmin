<?php

namespace App\Services;

use App\Mail\RetreatCashPaymentAdminMail;
use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Models\User;
use App\Support\SuperAdminRecipientResolver;

/**
 * Prévient les super_admin par e-mail et cloche Filament lors d'une preuve de paiement cash.
 */
class RetreatCashPaymentAdminNotifier
{
    public function __construct(
        protected PanelNotificationDispatcher $panelNotifications,
        protected SuperAdminRecipientResolver $superAdminRecipients,
    ) {}

    /**
     * Envoie e-mail + notification Filament à chaque super_admin actif possédant une adresse e-mail.
     *
     * @param RetreatParticipant $participant Participant ayant soumis la preuve
     * @param RetreatPayment $payment Paiement cash en attente
     * @param ChurchEvent $event Événement retraite
     * @return void
     */
    public function notify(RetreatParticipant $participant, RetreatPayment $payment, ChurchEvent $event): void
    {
        $participant->loadMissing(['event']);
        $admins = $this->superAdminRecipients->recipientsForPanelNotifications();
        $emailRecipients = $this->superAdminRecipients->recipientsForEmail();
        $title = 'Paiement cash à valider';
        $message = sprintf(
            '%s a soumis une preuve de paiement en espèces pour %s (réf. %s).',
            $participant->full_name,
            $event->name,
            $payment->reference
        );
        $link = $this->participantAdminUrl($participant);

        if ($admins->isEmpty() && $emailRecipients->isEmpty()) {
            return;
        }

        if ($admins->isNotEmpty()) {
            $this->panelNotifications->notify(
                $admins,
                $title,
                $message,
                $link,
                'payment',
                $participant
            );
        }

        foreach ($emailRecipients as $admin) {
            try {
                Mail::to($admin->email)->send(
                    new RetreatCashPaymentAdminMail($participant, $payment, $event)
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * @param RetreatParticipant $participant Participant
     * @return string|null URL admin Filament
     */
    protected function participantAdminUrl(RetreatParticipant $participant): ?string
    {
        try {
            return \App\Filament\Resources\RetreatParticipants\RetreatParticipantResource::getUrl(
                'view',
                ['record' => $participant]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }
}
