<?php

namespace App\Jobs;

use App\Models\SmsMessageLog;
use App\Models\User;
use App\Services\KeccelSmsService;
use App\Services\PanelNotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Interroge Keccel pour actualiser les accusés de réception d’une liste de SMS.
 */
class RefreshSmsDeliveriesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<int>  $logIds  IDs sms_message_logs
     * @param  int|null  $notifyUserId  Admin à notifier (optionnel)
     */
    public function __construct(
        public array $logIds,
        public ?int $notifyUserId = null,
    ) {}

    /**
     * @param  KeccelSmsService  $sms  Service Keccel
     * @param  PanelNotificationDispatcher  $notifier  Notifications Filament
     */
    public function handle(KeccelSmsService $sms, PanelNotificationDispatcher $notifier): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $this->logIds))));
        if ($ids === []) {
            return;
        }

        $logs = SmsMessageLog::query()
            ->whereIn('id', $ids)
            ->whereNotNull('provider_reference')
            ->get();

        $result = $sms->refreshDeliveries($logs);

        if ($this->notifyUserId) {
            $user = User::query()->find($this->notifyUserId);
            if ($user instanceof User) {
                $notifier->notify(
                    [$user],
                    'Accusés SMS actualisés',
                    "Vérifiés : {$result['checked']} — Livrés : {$result['delivered']} — Échecs DLR : {$result['failed']} — Erreurs : {$result['errors']}",
                    null,
                    $result['errors'] > 0 || $result['failed'] > 0 ? 'warning' : 'success',
                );
            }
        }
    }
}
