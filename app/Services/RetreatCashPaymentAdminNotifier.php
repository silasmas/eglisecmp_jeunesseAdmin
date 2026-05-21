<?php

namespace App\Services;

use App\Mail\RetreatCashPaymentAdminMail;
use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Prévient les administrateurs (e-mail, SMS, cloche Filament) lors d'une preuve de paiement cash.
 */
class RetreatCashPaymentAdminNotifier
{
    public function __construct(
        protected KeccelSmsService $sms,
        protected PanelNotificationDispatcher $panelNotifications,
    ) {}

    /**
     * Envoie les notifications aux administrateurs actifs.
     */
    public function notify(RetreatParticipant $participant, RetreatPayment $payment, ChurchEvent $event): void
    {
        $participant->loadMissing(['event']);
        $admins = $this->resolveAdminRecipients();
        $title = 'Paiement cash à valider';
        $message = sprintf(
            '%s a soumis une preuve de paiement en espèces pour %s (réf. %s).',
            $participant->full_name,
            $event->name,
            $payment->reference
        );
        $link = $this->participantAdminUrl($participant);

        if ($admins->isNotEmpty()) {
            $this->panelNotifications->notify(
                $admins,
                $title,
                $message,
                $link,
                'payment',
                $participant
            );

            foreach ($admins as $admin) {
                if (! filled($admin->email)) {
                    continue;
                }
                try {
                    Mail::to($admin->email)->send(
                        new RetreatCashPaymentAdminMail($participant, $payment, $event)
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $this->notifyAdminsBySms($participant, $payment, $event);
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function resolveAdminRecipients()
    {
        return User::query()
            ->role(['super_admin', 'panel_user'])
            ->where('is_active', true)
            ->get();
    }

    /**
     * Envoie un SMS aux numéros administrateurs configurés et aux téléphones des admins actifs.
     */
    protected function notifyAdminsBySms(
        RetreatParticipant $participant,
        RetreatPayment $payment,
        ChurchEvent $event
    ): void {
        $phones = $this->resolveAdminPhoneNumbers();

        if ($phones === []) {
            Log::channel('daily')->info('SMS admin cash : aucun numéro configuré.', [
                'participant_id' => $participant->id,
                'reference' => $payment->reference,
            ]);

            return;
        }

        $body = __('retraite.sms_admin_cash_body', [
            'name' => $participant->full_name,
            'event' => $event->name,
            'ref' => $payment->reference,
        ]);

        foreach ($phones as $phone) {
            try {
                $this->sms->send($phone, $body, 'retreat_cash_payment_admin');
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * @return list<string>
     */
    protected function resolveAdminPhoneNumbers(): array
    {
        $phones = [];

        foreach ($this->resolveAdminRecipients() as $admin) {
            if (filled($admin->telephone)) {
                $phones[] = (string) $admin->telephone;
            }
        }

        foreach (config('retraite.admin_notify_phones', []) as $configured) {
            if (filled($configured)) {
                $phones[] = (string) $configured;
            }
        }

        return array_values(array_unique(array_filter($phones)));
    }

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
