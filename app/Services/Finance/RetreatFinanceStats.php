<?php

namespace App\Services\Finance;

use App\Models\RetreatPayment;
use App\Models\RetreatPaymentFailureAlert;
use App\Models\RetreatVoluntaryDonation;
use App\Support\RetreatActiveEventScope;
use App\Support\RetreatPaymentFailureAlertsSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrège les indicateurs financiers (inscriptions + dons) pour le dashboard compta.
 *
 * Les montants sont toujours séparés par devise (jamais sommés USD+CDF).
 */
class RetreatFinanceStats
{
    /**
     * Synthèse complète pour le tableau de bord finances.
     *
     * @return array{
     *     payments: array{
     *         counts: array{total: int, paid: int, pending: int, failed: int, refunded: int},
     *         collected_by_currency: array<string, float>,
     *         expected_pending_by_currency: array<string, float>,
     *         failed_expected_by_currency: array<string, float>,
     *         paid_by_channel: array<string, array{count: int, by_currency: array<string, float>}>,
     *         unacked_failures: int
     *     },
     *     donations: array{
     *         counts: array{paid_cash: int, pending: int, cash_to_validate: int, in_kind: int, cancelled: int},
     *         collected_by_currency: array<string, float>,
     *         pending_by_currency: array<string, float>,
     *         cash_to_validate_by_currency: array<string, float>
     *     }
     * }
     */
    public function summarize(): array
    {
        return [
            'payments' => $this->summarizePayments(),
            'donations' => $this->summarizeDonations(),
        ];
    }

    /**
     * @return array{
     *     counts: array{total: int, paid: int, pending: int, failed: int, refunded: int},
     *     collected_by_currency: array<string, float>,
     *     expected_pending_by_currency: array<string, float>,
     *     failed_expected_by_currency: array<string, float>,
     *     paid_by_channel: array<string, array{count: int, by_currency: array<string, float>}>,
     *     unacked_failures: int
     * }
     */
    public function summarizePayments(): array
    {
        $base = RetreatActiveEventScope::applyToPayments(RetreatPayment::query());

        $paid = (clone $base)->where('etat', 'payee');
        $pending = (clone $base)->whereIn('etat', ['init', 'en_cours']);
        $failed = (clone $base)->whereIn('etat', ['echouee', 'annulee']);
        $refunded = (clone $base)->where('etat', 'remboursee');

        $paidByChannel = [];
        $channelRows = (clone $paid)
            ->select('channel', 'currency', DB::raw('COUNT(*) as aggregate_count'))
            ->selectRaw($this->receivedAmountSql().' as aggregate_amount')
            ->groupBy('channel', 'currency')
            ->get();

        foreach ($channelRows as $row) {
            $channel = (string) ($row->channel ?: 'inconnu');
            $currency = strtoupper((string) ($row->currency ?: 'USD'));
            $paidByChannel[$channel] ??= ['count' => 0, 'by_currency' => []];
            $paidByChannel[$channel]['count'] += (int) $row->aggregate_count;
            $paidByChannel[$channel]['by_currency'][$currency] =
                ($paidByChannel[$channel]['by_currency'][$currency] ?? 0)
                + (float) $row->aggregate_amount;
        }

        ksort($paidByChannel);

        return [
            'counts' => [
                'total' => (clone $base)->count(),
                'paid' => (clone $paid)->count(),
                'pending' => (clone $pending)->count(),
                'failed' => (clone $failed)->count(),
                'refunded' => (clone $refunded)->count(),
            ],
            'collected_by_currency' => $this->sumReceivedByCurrency($paid),
            'expected_pending_by_currency' => $this->sumExpectedByCurrency($pending),
            'failed_expected_by_currency' => $this->sumExpectedByCurrency($failed),
            'paid_by_channel' => $paidByChannel,
            'unacked_failures' => $this->unacknowledgedFailureCount(),
        ];
    }

    /**
     * @return array{
     *     counts: array{paid_cash: int, pending: int, cash_to_validate: int, in_kind: int, cancelled: int},
     *     collected_by_currency: array<string, float>,
     *     pending_by_currency: array<string, float>,
     *     cash_to_validate_by_currency: array<string, float>
     * }
     */
    public function summarizeDonations(): array
    {
        if (! Schema::hasTable('retreat_voluntary_donations')) {
            return [
                'counts' => [
                    'paid_cash' => 0,
                    'pending' => 0,
                    'cash_to_validate' => 0,
                    'in_kind' => 0,
                    'cancelled' => 0,
                ],
                'collected_by_currency' => [],
                'pending_by_currency' => [],
                'cash_to_validate_by_currency' => [],
            ];
        }

        $base = $this->operationalDonationsQuery();

        $paidCash = (clone $base)
            ->where('donation_kind', RetreatVoluntaryDonation::KIND_CASH)
            ->where('status', RetreatVoluntaryDonation::STATUS_PAID);

        $pending = (clone $base)->where('status', RetreatVoluntaryDonation::STATUS_PENDING);
        $cashToValidate = (clone $base)->where('status', RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED);
        $inKind = (clone $base)
            ->where('donation_kind', RetreatVoluntaryDonation::KIND_IN_KIND)
            ->whereIn('status', [
                RetreatVoluntaryDonation::STATUS_SUBMITTED,
                RetreatVoluntaryDonation::STATUS_PAID,
            ]);
        $cancelled = (clone $base)->where('status', RetreatVoluntaryDonation::STATUS_CANCELLED);

        return [
            'counts' => [
                'paid_cash' => (clone $paidCash)->count(),
                'pending' => (clone $pending)->count(),
                'cash_to_validate' => (clone $cashToValidate)->count(),
                'in_kind' => (clone $inKind)->count(),
                'cancelled' => (clone $cancelled)->count(),
            ],
            'collected_by_currency' => $this->sumDonationReceivedByCurrency($paidCash),
            'pending_by_currency' => $this->sumDonationExpectedByCurrency($pending),
            'cash_to_validate_by_currency' => $this->sumDonationExpectedByCurrency($cashToValidate),
        ];
    }

    /**
     * Formate une map devise => montant pour l’UI.
     *
     * @param  array<string, float>  $byCurrency
     */
    public function formatMoneyMap(array $byCurrency, string $empty = '0'): string
    {
        if ($byCurrency === []) {
            return $empty;
        }

        ksort($byCurrency);
        $parts = [];
        foreach ($byCurrency as $currency => $amount) {
            $parts[] = number_format((float) $amount, 2, '.', ' ').' '.$currency;
        }

        return implode(' · ', $parts);
    }

    /**
     * Libellé canal pour l’UI.
     */
    public function channelLabel(string $channel): string
    {
        return match ($channel) {
            'mobile_money' => 'Mobile Money',
            'card' => 'Carte bancaire',
            'cash' => 'Espèces (cash)',
            'sponsorship_voucher' => 'Code parrainage',
            default => ucfirst(str_replace('_', ' ', $channel)),
        };
    }

    /**
     * Données canal payé, avec zéro si aucun encaissement.
     *
     * @param  array<string, array{count: int, by_currency: array<string, float>}>  $paidByChannel
     * @return array{count: int, by_currency: array<string, float>}
     */
    public function channelPaidOrEmpty(array $paidByChannel, string $channel): array
    {
        return $paidByChannel[$channel] ?? ['count' => 0, 'by_currency' => []];
    }

    /**
     * Expression SQL du montant encaissé (alignée sur resolveReceivedAmount).
     */
    protected function receivedAmountSql(): string
    {
        return 'SUM(CASE
            WHEN channel = \'cash\' AND (amount_paid IS NULL OR amount_paid = 0)
                THEN COALESCE(amount_expected, 0)
            ELSE COALESCE(amount_paid, 0)
        END)';
    }

    /**
     * @param  Builder<RetreatPayment>  $query
     * @return array<string, float>
     */
    protected function sumReceivedByCurrency(Builder $query): array
    {
        return (clone $query)
            ->select('currency')
            ->selectRaw($this->receivedAmountSql().' as aggregate_amount')
            ->groupBy('currency')
            ->orderBy('currency')
            ->pluck('aggregate_amount', 'currency')
            ->map(fn ($amount): float => (float) $amount)
            ->all();
    }

    /**
     * @param  Builder<RetreatPayment>  $query
     * @return array<string, float>
     */
    protected function sumExpectedByCurrency(Builder $query): array
    {
        return (clone $query)
            ->select('currency', DB::raw('SUM(COALESCE(amount_expected, 0)) as aggregate_amount'))
            ->groupBy('currency')
            ->orderBy('currency')
            ->pluck('aggregate_amount', 'currency')
            ->map(fn ($amount): float => (float) $amount)
            ->all();
    }

    /**
     * @param  Builder<RetreatVoluntaryDonation>  $query
     * @return array<string, float>
     */
    protected function sumDonationReceivedByCurrency(Builder $query): array
    {
        return (clone $query)
            ->select('currency')
            ->selectRaw('SUM(CASE
                WHEN amount_paid IS NULL OR amount_paid = 0 THEN COALESCE(amount_expected, 0)
                ELSE amount_paid
            END) as aggregate_amount')
            ->groupBy('currency')
            ->orderBy('currency')
            ->pluck('aggregate_amount', 'currency')
            ->map(fn ($amount): float => (float) $amount)
            ->all();
    }

    /**
     * @param  Builder<RetreatVoluntaryDonation>  $query
     * @return array<string, float>
     */
    protected function sumDonationExpectedByCurrency(Builder $query): array
    {
        return (clone $query)
            ->select('currency', DB::raw('SUM(COALESCE(amount_expected, 0)) as aggregate_amount'))
            ->groupBy('currency')
            ->orderBy('currency')
            ->pluck('aggregate_amount', 'currency')
            ->map(fn ($amount): float => (float) $amount)
            ->all();
    }

    /**
     * @return Builder<RetreatVoluntaryDonation>
     */
    protected function operationalDonationsQuery(): Builder
    {
        return RetreatVoluntaryDonation::query()->where(function (Builder $inner): void {
            $inner->whereNull('event_id')
                ->orWhereHas(
                    'event',
                    fn (Builder $eventQuery): Builder => $eventQuery->whereNull('archived_at')
                );
        });
    }

    protected function unacknowledgedFailureCount(): int
    {
        if (! RetreatPaymentFailureAlertsSchema::isReady()) {
            return 0;
        }

        return RetreatPaymentFailureAlert::query()
            ->whereNull('acknowledged_at')
            ->count();
    }
}
