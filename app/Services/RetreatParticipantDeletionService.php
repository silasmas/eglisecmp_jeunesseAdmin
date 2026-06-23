<?php

namespace App\Services;

use App\Models\RetreatActivityAttendance;
use App\Models\RetreatParticipant;
use App\Models\RetreatParticipantDeletionLog;
use App\Models\RetreatParticipantMovement;
use App\Models\RetreatPayment;
use App\Models\RetreatPolicyAcknowledgement;
use App\Models\RetreatSponsorshipVoucher;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Suppression sécurisée de participants retraite avec aperçu et journal d'audit.
 */
class RetreatParticipantDeletionService
{
    /**
     * @param Collection<int, RetreatParticipant>|EloquentCollection<int, RetreatParticipant> $participants Participants ciblés
     * @return EloquentCollection<int, RetreatParticipant>
     */
    public function loadForDeletionPreview(Collection|EloquentCollection $participants): EloquentCollection
    {
        $ids = $participants->pluck('id')->filter()->values();

        return RetreatParticipant::query()
            ->whereIn('id', $ids)
            ->with([
                'event',
                'chambre',
                'atelier',
                'latestPayment',
                'sponsorshipVoucher',
            ])
            ->withCount([
                'payments',
                'activityAttendances',
                'movements',
            ])
            ->get();
    }

    /**
     * Construit l'aperçu des données qui seront supprimées.
     *
     * @param Collection<int, RetreatParticipant>|EloquentCollection<int, RetreatParticipant> $participants Participants ciblés
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     totals: array<string, int>,
     *     participants_summary: string,
     *     related_summary: string,
     *     event_id: int|null
     * }
     */
    public function buildPreview(Collection|EloquentCollection $participants): array
    {
        $loaded = $this->loadForDeletionPreview($participants);
        $participantIds = $loaded->pluck('id')->all();
        $paymentIds = RetreatPayment::query()
            ->whereIn('participant_id', $participantIds)
            ->pluck('id')
            ->all();

        $transactionCount = $paymentIds === []
            ? 0
            : (int) DB::table('retreat_payment_transactions')->whereIn('payment_id', $paymentIds)->count();

        $failureAlertCount = $participantIds === []
            ? 0
            : (int) DB::table('retreat_payment_failure_alerts')
                ->where(function ($query) use ($participantIds, $paymentIds): void {
                    $query->whereIn('participant_id', $participantIds);

                    if ($paymentIds !== []) {
                        $query->orWhereIn('retreat_payment_id', $paymentIds);
                    }
                })
                ->count();

        $policyAckCount = $participantIds === []
            ? 0
            : (int) RetreatPolicyAcknowledgement::query()->whereIn('participant_id', $participantIds)->count();

        $sponsorshipCount = $loaded->filter(fn (RetreatParticipant $participant): bool => $participant->sponsorshipVoucher !== null)->count();

        $rows = [];
        $participantSummaries = [];

        foreach ($loaded as $participant) {
            $rows[] = $this->buildPreviewRow($participant);
            $participantSummaries[] = $this->compactParticipantSummary($participant);
        }

        $totals = [
            'participants' => $loaded->count(),
            'payments' => (int) $loaded->sum('payments_count'),
            'transactions' => $transactionCount,
            'attendances' => (int) $loaded->sum('activity_attendances_count'),
            'movements' => (int) $loaded->sum('movements_count'),
            'policy_acknowledgements' => $policyAckCount,
            'payment_failure_alerts' => $failureAlertCount,
            'sponsorship_vouchers' => $sponsorshipCount,
        ];

        $eventIds = $loaded->pluck('event_id')->filter()->unique()->values();

        return [
            'rows' => $rows,
            'totals' => $totals,
            'participants_summary' => implode(' || ', $participantSummaries),
            'related_summary' => $this->compactRelatedSummary($totals),
            'event_id' => $eventIds->count() === 1 ? (int) $eventIds->first() : null,
        ];
    }

    /**
     * Supprime les participants et enregistre l'historique.
     *
     * @param Collection<int, RetreatParticipant>|EloquentCollection<int, RetreatParticipant> $participants Participants à supprimer
     * @param User $performedBy Administrateur auteur de l'action
     * @return array{deleted_count: int, log: RetreatParticipantDeletionLog}
     */
    public function deleteParticipants(Collection|EloquentCollection $participants, User $performedBy): array
    {
        $preview = $this->buildPreview($participants);
        $loaded = $this->loadForDeletionPreview($participants);
        $participantIds = $loaded->pluck('id')->all();

        $log = DB::transaction(function () use ($participantIds, $preview, $performedBy): RetreatParticipantDeletionLog {
            foreach ($participantIds as $participantId) {
                $this->deleteSingleParticipant((int) $participantId);
            }

            return RetreatParticipantDeletionLog::query()->create([
                'performed_by' => $performedBy->id,
                'event_id' => $preview['event_id'],
                'participant_count' => $preview['totals']['participants'],
                'participants_summary' => $preview['participants_summary'],
                'related_summary' => $preview['related_summary'],
            ]);
        });

        return [
            'deleted_count' => $preview['totals']['participants'],
            'log' => $log,
        ];
    }

    /**
     * @param array{
     *     rows: list<array<string, mixed>>,
     *     totals: array<string, int>
     * } $preview Aperçu buildPreview
     * @return HtmlString HTML pour modale Filament
     */
    public function renderPreviewHtml(array $preview): HtmlString
    {
        return new HtmlString(view('filament.partials.retreat-participant-deletion-preview', [
            'rows' => $preview['rows'],
            'totals' => $preview['totals'],
        ])->render());
    }

    /**
     * @param RetreatParticipant $participant Participant chargé pour aperçu
     * @return array<string, mixed> Ligne du tableau d'aperçu
     */
    protected function buildPreviewRow(RetreatParticipant $participant): array
    {
        return [
            'id' => $participant->id,
            'identity' => trim(sprintf(
                '%s %s %s',
                $participant->nom,
                $participant->postnom ?? '',
                $participant->prenom
            )),
            'email' => $participant->email ?: '—',
            'telephone' => $participant->telephone ?: '—',
            'event' => $participant->event?->name ?: '—',
            'registration_status' => $participant->registration_status ?: '—',
            'paiement_valide' => $participant->paiement_valide ? 'Oui' : 'Non',
            'chambre' => $participant->chambre?->nom ?: '—',
            'atelier' => $participant->atelier?->numero ? 'Atelier '.$participant->atelier->numero : '—',
            'payments_count' => (int) $participant->payments_count,
            'attendances_count' => (int) $participant->activity_attendances_count,
            'movements_count' => (int) $participant->movements_count,
            'sponsorship' => $participant->sponsorshipVoucher ? 'Oui ('.$participant->sponsorshipVoucher->code.')' : '—',
        ];
    }

    /**
     * @param RetreatParticipant $participant Participant
     * @return string Résumé compact d'un participant
     */
    protected function compactParticipantSummary(RetreatParticipant $participant): string
    {
        $parts = [
            'id='.$participant->id,
            'nom='.($participant->nom ?: '—'),
            'postnom='.($participant->postnom ?: '—'),
            'prénom='.($participant->prenom ?: '—'),
            'email='.($participant->email ?: '—'),
            'tél='.($participant->telephone ?: '—'),
            'statut='.($participant->registration_status ?: '—'),
            'paiement='.($participant->paiement_valide ? 'validé' : 'non'),
            'événement='.($participant->event?->name ?: '—'),
            'chambre='.($participant->chambre?->nom ?: '—'),
            'atelier='.($participant->atelier?->numero ? (string) $participant->atelier->numero : '—'),
        ];

        return implode(' / ', $parts);
    }

    /**
     * @param array<string, int> $totals Totaux des données liées
     * @return string Résumé compact des suppressions associées
     */
    protected function compactRelatedSummary(array $totals): string
    {
        return implode(' / ', [
            'participants='.$totals['participants'],
            'paiements='.$totals['payments'],
            'transactions='.$totals['transactions'],
            'pointages='.$totals['attendances'],
            'mouvements='.$totals['movements'],
            'accusés_politique='.$totals['policy_acknowledgements'],
            'alertes_paiement='.$totals['payment_failure_alerts'],
            'codes_parrainage='.$totals['sponsorship_vouchers'],
        ]);
    }

    /**
     * @param int $participantId Identifiant participant
     * @return void
     */
    protected function deleteSingleParticipant(int $participantId): void
    {
        $paymentIds = RetreatPayment::query()
            ->where('participant_id', $participantId)
            ->pluck('id')
            ->all();

        if ($paymentIds !== []) {
            DB::table('retreat_payment_failure_alerts')
                ->whereIn('retreat_payment_id', $paymentIds)
                ->delete();

            DB::table('retreat_payment_transactions')
                ->whereIn('payment_id', $paymentIds)
                ->delete();
        }

        DB::table('retreat_payment_failure_alerts')
            ->where('participant_id', $participantId)
            ->delete();

        RetreatPayment::query()->where('participant_id', $participantId)->delete();
        RetreatActivityAttendance::query()->where('participant_id', $participantId)->delete();
        RetreatParticipantMovement::query()->where('participant_id', $participantId)->delete();
        RetreatPolicyAcknowledgement::query()->where('participant_id', $participantId)->delete();

        RetreatSponsorshipVoucher::query()
            ->where('redeemed_by_participant_id', $participantId)
            ->get()
            ->each(function (RetreatSponsorshipVoucher $voucher): void {
                $updates = [
                    'redeemed_by_participant_id' => null,
                    'redeemed_at' => null,
                ];

                if ($voucher->redeemed_at !== null) {
                    $updates['uses_remaining'] = min(
                        (int) $voucher->uses_total,
                        (int) $voucher->uses_remaining + 1
                    );
                }

                $voucher->update($updates);
            });

        RetreatParticipant::query()->whereKey($participantId)->delete();
    }
}
