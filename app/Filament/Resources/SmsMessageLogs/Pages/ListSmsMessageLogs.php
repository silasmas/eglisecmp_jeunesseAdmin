<?php

namespace App\Filament\Resources\SmsMessageLogs\Pages;

use App\Filament\Resources\SmsMessageLogs\SmsMessageLogResource;
use App\Filament\Resources\SmsMessageLogs\Widgets\SmsMessageLogsStats;
use App\Jobs\RefreshSmsDeliveriesJob;
use App\Models\SmsMessageLog;
use App\Services\KeccelSmsService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

/**
 * Historique SMS avec stats d’envoi et action d’actualisation des accusés.
 */
class ListSmsMessageLogs extends ListRecords
{
    protected static string $resource = SmsMessageLogResource::class;

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            SmsMessageLogsStats::class,
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('backfillReferences')
                ->label('Récupérer les références')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->action(function (): void {
                    $n = app(KeccelSmsService::class)->backfillMissingProviderReferences();

                    Notification::make()
                        ->title('Références Keccel')
                        ->body($n > 0 ? "{$n} référence(s) récupérée(s) depuis les réponses d’envoi." : 'Aucune référence manquante à récupérer.')
                        ->success()
                        ->send();
                }),
            Action::make('refreshPendingDeliveries')
                ->label('Actualiser les accusés')
                ->icon('heroicon-o-signal')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Actualiser les accusés de réception')
                ->modalDescription('Interroge Keccel (v2/delivery.asp) pour les SMS envoyés dont le DLR est encore en attente (max. 100).')
                ->action(function (): void {
                    app(KeccelSmsService::class)->backfillMissingProviderReferences();

                    $ids = SmsMessageLog::query()
                        ->whereNotNull('provider_reference')
                        ->where('provider_reference', '!=', '')
                        ->where(function ($q): void {
                            $q->whereNull('delivery_status')
                                ->orWhereIn('delivery_status', ['PENDING', 'UNKNOWN', 'BUFFERED', 'ENROUTE', 'ACCEPTED']);
                        })
                        ->whereIn('status', ['sent', 'pending'])
                        ->orderByDesc('id')
                        ->limit(100)
                        ->pluck('id')
                        ->map(fn ($id): int => (int) $id)
                        ->all();

                    if ($ids === []) {
                        Notification::make()
                            ->title('Aucun SMS à vérifier')
                            ->body('Aucun envoi en attente d’accusé de réception.')
                            ->warning()
                            ->send();

                        return;
                    }

                    RefreshSmsDeliveriesJob::dispatch($ids, Auth::id() ? (int) Auth::id() : null);

                    Notification::make()
                        ->title('Vérification des accusés mise en file')
                        ->body(count($ids).' SMS seront vérifiés en arrière-plan.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
