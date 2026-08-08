<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ManageRetreatCashPayments;
use App\Filament\Pages\ManageRetreatDonationCashPayments;
use App\Filament\Pages\RetreatPaymentFailureMonitor;
use App\Filament\Resources\RetreatPayments\RetreatPaymentResource;
use App\Models\User;
use App\Services\Finance\RetreatFinanceStats;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Dashboard finances : encaissements inscriptions/dons, échecs, détails par canal/devise.
 */
class RetreatFinanceOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $pollingInterval = '120s';

    protected int|string|array $columnSpan = 'full';

    /**
     * Visible aux super_admin ou aux comptes pouvant lister les paiements.
     */
    public static function canView(): bool
    {
        $user = Auth::user();
        if (! $user instanceof User || ! $user->is_active) {
            return false;
        }

        return $user->hasRole('super_admin') || $user->can('ViewAny:RetreatPayment');
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $finance = app(RetreatFinanceStats::class);
        $data = $finance->summarize();
        $payments = $data['payments'];
        $donations = $data['donations'];

        $stats = [
            Stat::make(
                'Encaissé inscriptions',
                $finance->formatMoneyMap($payments['collected_by_currency'])
            )
                ->description(
                    $payments['counts']['paid'].' paiement(s) payé(s) — événements opérationnels'
                )
                ->color('success')
                ->url($this->safeResourceUrl(RetreatPaymentResource::class)),
            Stat::make(
                'En attente (à encaisser)',
                $finance->formatMoneyMap($payments['expected_pending_by_currency'])
            )
                ->description(
                    $payments['counts']['pending'].' transaction(s) init / en cours'
                )
                ->color('warning'),
            Stat::make(
                'Échecs / annulés',
                (string) $payments['counts']['failed']
            )
                ->description(
                    'Montant concerné : '.$finance->formatMoneyMap($payments['failed_expected_by_currency'])
                    .($payments['unacked_failures'] > 0
                        ? ' — '.$payments['unacked_failures'].' alerte(s) non traitée(s)'
                        : '')
                )
                ->color('danger')
                ->url($this->safePageUrl(RetreatPaymentFailureMonitor::class)),
            Stat::make(
                'Dons cash encaissés',
                $finance->formatMoneyMap($donations['collected_by_currency'])
            )
                ->description(
                    $donations['counts']['paid_cash'].' don(s) payé(s)'
                    .($donations['counts']['cash_to_validate'] > 0
                        ? ' — '.$donations['counts']['cash_to_validate'].' cash à valider ('
                            .$finance->formatMoneyMap($donations['cash_to_validate_by_currency']).')'
                        : '')
                )
                ->color('success')
                ->url($this->safePageUrl(ManageRetreatDonationCashPayments::class)),
        ];

        foreach ($payments['paid_by_channel'] as $channel => $channelData) {
            $stats[] = Stat::make(
                'Encaissé · '.$finance->channelLabel($channel),
                $finance->formatMoneyMap($channelData['by_currency'])
            )
                ->description($channelData['count'].' paiement(s) payé(s)')
                ->color(match ($channel) {
                    'cash' => 'primary',
                    'mobile_money' => 'info',
                    'card' => 'gray',
                    'sponsorship_voucher' => 'warning',
                    default => 'gray',
                });
        }

        $stats[] = Stat::make(
            'Cash inscriptions à suivre',
            (string) ($payments['paid_by_channel']['cash']['count'] ?? 0)
        )
            ->description('Paiements cash déjà validés (détail dans Gestion cash)')
            ->color('primary')
            ->url($this->safePageUrl(ManageRetreatCashPayments::class));

        $stats[] = Stat::make(
            'Dons en nature / annulés',
            (string) $donations['counts']['in_kind']
        )
            ->description(
                'Nature : '.$donations['counts']['in_kind']
                .' — Annulés : '.$donations['counts']['cancelled']
                .' — Électronique en attente : '.$donations['counts']['pending']
                .' ('.$finance->formatMoneyMap($donations['pending_by_currency']).')'
            )
            ->color('gray');

        if ($payments['counts']['refunded'] > 0) {
            $stats[] = Stat::make('Remboursés', (string) $payments['counts']['refunded'])
                ->description('État remboursee')
                ->color('warning');
        }

        return $stats;
    }

    /**
     * @param  class-string  $resource
     */
    protected function safeResourceUrl(string $resource): ?string
    {
        try {
            return $resource::getUrl('index');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  class-string  $page
     */
    protected function safePageUrl(string $page): ?string
    {
        try {
            if (method_exists($page, 'getUrl')) {
                return $page::getUrl();
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
