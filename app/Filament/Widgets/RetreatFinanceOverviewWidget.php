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
 * Dashboard finances : encaissements inscriptions/dons, canaux (carte, MM, cash), échecs.
 */
class RetreatFinanceOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $heading = 'Finances & comptabilité';

    protected ?string $description = 'Suivi des encaissements retraite (événements opérationnels). Montants séparés par devise (USD / CDF).';

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
     * @return int|array<string, int>|null
     */
    protected function getColumns(): int|array|null
    {
        return [
            'default' => 1,
            'sm' => 2,
            'xl' => 3,
            '2xl' => 4,
        ];
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

        $card = $finance->channelPaidOrEmpty($payments['paid_by_channel'], 'card');
        $mobile = $finance->channelPaidOrEmpty($payments['paid_by_channel'], 'mobile_money');
        $cash = $finance->channelPaidOrEmpty($payments['paid_by_channel'], 'cash');
        $voucher = $finance->channelPaidOrEmpty($payments['paid_by_channel'], 'sponsorship_voucher');

        $stats = [
            Stat::make(
                'Total encaissé (inscriptions)',
                $finance->formatMoneyMap($payments['collected_by_currency'])
            )
                ->description(
                    'Somme de tous les paiements d’inscription au statut « payée » (tous canaux). '
                    .$payments['counts']['paid'].' paiement(s) validé(s). Cliquez pour ouvrir la liste des paiements.'
                )
                ->color('success')
                ->url($this->safeResourceUrl(RetreatPaymentResource::class)),

            Stat::make(
                'Carte bancaire (FlexPay)',
                $finance->formatMoneyMap($card['by_currency'])
            )
                ->description(
                    'Argent réellement encaissé via paiement par carte (Visa/Mastercard FlexPay). '
                    .$card['count'].' transaction(s) payée(s). Utile pour rapprocher les virements carte du compte.'
                )
                ->color('info'),

            Stat::make(
                'Mobile Money (FlexPay)',
                $finance->formatMoneyMap($mobile['by_currency'])
            )
                ->description(
                    'Encaissements M-Pesa / Airtel / Orange / Afri Money validés. '
                    .$mobile['count'].' transaction(s) payée(s). À rapprocher des relevés opérateurs.'
                )
                ->color('info'),

            Stat::make(
                'Espèces (cash) validées',
                $finance->formatMoneyMap($cash['by_currency'])
            )
                ->description(
                    'Paiements cash d’inscription déjà validés en admin (caisse). '
                    .$cash['count'].' reçu(s). Cliquez pour la file de validation cash.'
                )
                ->color('primary')
                ->url($this->safePageUrl(ManageRetreatCashPayments::class)),
        ];

        if ($voucher['count'] > 0) {
            $stats[] = Stat::make(
                'Codes parrainage',
                $finance->formatMoneyMap($voucher['by_currency'])
            )
                ->description(
                    'Inscriptions soldées par un code de prise en charge (don parrain). '
                    .$voucher['count'].' — ne correspond pas toujours à un encaissement cash du jour.'
                )
                ->color('warning');
        }

        $stats[] = Stat::make(
            'En attente d’encaissement',
            $finance->formatMoneyMap($payments['expected_pending_by_currency'])
        )
            ->description(
                'Montants encore dus : paiements initiés ou en cours (carte/MM non confirmés, cash non validé). '
                .$payments['counts']['pending'].' dossier(s). Ce n’est pas de l’argent déjà en caisse.'
            )
            ->color('warning');

        $stats[] = Stat::make(
            'Échecs & annulations',
            (string) $payments['counts']['failed']
        )
            ->description(
                'Nombre de paiements échoués ou annulés (pas encaissés). Montant théorique perdu/abandonné : '
                .$finance->formatMoneyMap($payments['failed_expected_by_currency'])
                .($payments['unacked_failures'] > 0
                    ? ' — '.$payments['unacked_failures'].' alerte(s) à traiter.'
                    : ' — aucune alerte en attente.')
                .' Cliquez pour le moniteur d’échecs.'
            )
            ->color('danger')
            ->url($this->safePageUrl(RetreatPaymentFailureMonitor::class));

        $stats[] = Stat::make(
            'Dons cash encaissés',
            $finance->formatMoneyMap($donations['collected_by_currency'])
        )
            ->description(
                'Dons volontaires en espèces/électroniques au statut « paid » (hors inscriptions). '
                .$donations['counts']['paid_cash'].' don(s).'
                .($donations['counts']['cash_to_validate'] > 0
                    ? ' Attention : '.$donations['counts']['cash_to_validate'].' don(s) cash à valider ('
                        .$finance->formatMoneyMap($donations['cash_to_validate_by_currency']).').'
                    : '')
            )
            ->color('success')
            ->url($this->safePageUrl(ManageRetreatDonationCashPayments::class));

        $stats[] = Stat::make(
            'Dons en attente / nature',
            (string) ($donations['counts']['pending'] + $donations['counts']['in_kind'])
        )
            ->description(
                'Suivi dons non encore clôturés : électronique en attente = '.$donations['counts']['pending']
                .' ('.$finance->formatMoneyMap($donations['pending_by_currency']).')'
                .' — en nature = '.$donations['counts']['in_kind']
                .' — annulés = '.$donations['counts']['cancelled'].'.'
            )
            ->color('gray');

        if ($payments['counts']['refunded'] > 0) {
            $stats[] = Stat::make('Remboursements', (string) $payments['counts']['refunded'])
                ->description('Paiements marqués « remboursée » — à traiter côté comptabilité / caisse.')
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
