<?php

namespace App\Jobs;

use App\Models\RetreatParticipant;
use App\Models\User;
use App\Services\KeccelSmsService;
use App\Services\PanelNotificationDispatcher;
use App\Services\Sms\SmsTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envoie une campagne SMS (participants + numéros manuels) via Keccel, sans bloquer la requête HTTP.
 */
class SendSmsCampaignJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Délai soft entre envois (µs) pour ne pas saturer Keccel.
     */
    public const DELAY_MICROSECONDS = 150000;

    /**
     * @param  int  $requestedByUserId  Admin ayant lancé la campagne
     * @param  string  $body  Corps modèle avec {{variables}}
     * @param  list<array{type: string, phone: string, participant_id?: int|null}>  $recipients  Destinataires normalisés
     */
    public function __construct(
        public int $requestedByUserId,
        public string $body,
        public array $recipients,
    ) {}

    /**
     * @param  KeccelSmsService  $sms  Service d’envoi
     * @param  SmsTemplateRenderer  $renderer  Moteur de variables
     * @param  PanelNotificationDispatcher  $notifier  Notifications Filament
     */
    public function handle(
        KeccelSmsService $sms,
        SmsTemplateRenderer $renderer,
        PanelNotificationDispatcher $notifier,
    ): void {
        $sent = 0;
        $failed = 0;
        $logIds = [];

        foreach ($this->recipients as $index => $recipient) {
            $phone = (string) ($recipient['phone'] ?? '');
            if ($phone === '') {
                $failed++;

                continue;
            }

            $participant = null;
            $participantId = $recipient['participant_id'] ?? null;
            if (filled($participantId)) {
                $participant = RetreatParticipant::query()->find((int) $participantId);
            }

            $message = $renderer->render($this->body, $participant);

            try {
                $sms->send($phone, $message, 'sms_campaign');
                $sent++;
                $logId = $sms->lastLog()?->id;
                if ($logId) {
                    $logIds[] = (int) $logId;
                }
            } catch (Throwable $e) {
                report($e);
                $failed++;
                Log::channel('daily')->warning('Campagne SMS : échec individuel', [
                    'phone' => $phone,
                    'participant_id' => $participantId,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($index < count($this->recipients) - 1) {
                usleep(self::DELAY_MICROSECONDS);
            }
        }

        $user = User::query()->find($this->requestedByUserId);
        if ($user instanceof User) {
            $notifier->notify(
                [$user],
                'Campagne SMS terminée',
                "Envoyés : {$sent} — Échecs : {$failed} — Total : ".count($this->recipients)
                .($logIds !== [] ? ' — accusés de réception en cours de vérification…' : ''),
                null,
                $failed > 0 ? 'warning' : 'success',
            );
        }

        // DLR Keccel souvent disponibles après quelques secondes/minutes.
        if ($logIds !== []) {
            RefreshSmsDeliveriesJob::dispatch($logIds, $this->requestedByUserId)
                ->delay(now()->addMinutes(2));
        }
    }
}
