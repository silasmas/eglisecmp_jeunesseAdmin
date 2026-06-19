<?php

namespace App\Services;

use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Actions admin sur l'inscription participant (validation, billet, badge).
 */
class RetreatParticipantRegistrationService
{
    public function __construct(
        protected RetreatRegistrationFulfillmentService $fulfillment,
    ) {}

    /**
     * Valide le paiement cash et l'inscription du participant.
     *
     * @param RetreatPayment $payment Paiement cash
     * @param User $admin Administrateur
     * @return array{success: bool, message: string, channel: string|null} Résultat envoi billet
     */
    public function approveCashPayment(RetreatPayment $payment, User $admin): array
    {
        if ($payment->channel !== 'cash') {
            throw new \InvalidArgumentException('Seuls les paiements cash peuvent être validés depuis cette file.');
        }

        $payment->update([
            'etat' => 'payee',
            'access_granted' => true,
            'access_granted_at' => now(),
            'access_granted_by' => $admin->id,
            'paid_at' => $payment->paid_at ?? now(),
            'amount_paid' => (float) ($payment->amount_paid ?: $payment->amount_expected),
            'provider_message' => 'Paiement cash validé par '.$admin->name,
        ]);

        return $this->finalizeCashRegistration($payment->fresh(['participant', 'event']));
    }

    /**
     * Marque le participant payé et envoie le billet (e-mail ou SMS).
     *
     * @param RetreatPayment $payment Paiement cash validé
     * @return array{success: bool, message: string, channel: string|null}
     */
    protected function finalizeCashRegistration(RetreatPayment $payment): array
    {
        $participant = $payment->participant;

        if (! $participant) {
            return [
                'success' => false,
                'message' => 'Participant introuvable pour ce paiement.',
                'channel' => null,
            ];
        }

        $participant->update([
            'paiement_valide' => true,
            'registration_status' => 'completed',
            'preuve_paiement' => $participant->preuve_paiement
                ?: ($payment->provider_reference ?? $payment->reference),
        ]);

        $this->fulfillment->fulfillIfNeeded($payment->fresh(['participant', 'event']));

        $participant->refresh();

        if ($participant->billet_envoye_email || $participant->billet_envoye_whatsapp) {
            $channel = $participant->billet_envoye_email ? 'email' : 'sms';

            return [
                'success' => true,
                'message' => $channel === 'email'
                    ? 'Billet envoyé par e-mail au participant.'
                    : 'Billet envoyé par SMS au participant.',
                'channel' => $channel,
            ];
        }

        return [
            'success' => false,
            'message' => 'Paiement validé, mais le billet n’a pas pu être envoyé. Vérifiez l’e-mail ou le téléphone du participant et la configuration SMTP du serveur.',
            'channel' => null,
        ];
    }

    /**
     * Rejette un paiement cash soumis.
     *
     * @param RetreatPayment $payment Paiement cash
     * @param User $admin Administrateur
     * @param string|null $reason Motif optionnel
     * @return void
     */
    public function rejectCashPayment(RetreatPayment $payment, User $admin, ?string $reason = null): void
    {
        if ($payment->channel !== 'cash') {
            throw new \InvalidArgumentException('Seuls les paiements cash peuvent être rejetés depuis cette file.');
        }

        DB::transaction(function () use ($payment, $admin, $reason): void {
            $payment->update([
                'etat' => 'annulee',
                'access_granted' => false,
                'access_granted_at' => null,
                'access_granted_by' => $admin->id,
                'provider_message' => $reason ?: 'Paiement cash rejeté par '.$admin->name,
            ]);

            $participant = $payment->participant;
            if ($participant) {
                $participant->update([
                    'paiement_valide' => false,
                    'registration_status' => 'rejected',
                ]);
            }
        });
    }

    /**
     * Valide l'inscription (paiement + statut participant).
     *
     * @param RetreatParticipant $participant Participant
     * @param User $admin Administrateur
     * @return void
     */
    public function validateRegistration(RetreatParticipant $participant, User $admin): void
    {
        $payment = $this->resolveLatestPayment($participant);

        if ($payment && $payment->channel === 'cash' && ($payment->etat !== 'payee' || ! $payment->access_granted)) {
            $this->approveCashPayment($payment, $admin);

            return;
        }

        if ($payment && ($payment->etat !== 'payee' || ! $payment->access_granted)) {
            $payment->update([
                'etat' => 'payee',
                'access_granted' => true,
                'access_granted_at' => now(),
                'access_granted_by' => $admin->id,
                'paid_at' => $payment->paid_at ?? now(),
            ]);
        }

        $participant->update([
            'paiement_valide' => true,
            'registration_status' => 'completed',
            'preuve_paiement' => $participant->preuve_paiement
                ?: ($payment?->provider_reference ?? $payment?->reference),
        ]);

        if ($payment) {
            $this->fulfillment->fulfillIfNeeded($payment->fresh(['participant', 'event']));
        }
    }

    /**
     * Envoie ou renvoie la notification billet.
     *
     * @param RetreatParticipant $participant Participant
     * @param bool $forceResend Forcer le renvoi même si déjà envoyé
     * @return array{success: bool, message: string, channel: string|null}
     */
    public function sendBilletNotification(RetreatParticipant $participant, bool $forceResend = false): array
    {
        if (! $participant->paiement_valide) {
            return [
                'success' => false,
                'message' => 'Validez d\'abord le paiement avant d\'envoyer le billet.',
                'channel' => null,
            ];
        }

        $payment = $this->resolveLatestPayment($participant);
        if (! $payment) {
            return [
                'success' => false,
                'message' => 'Aucun paiement associé à ce participant.',
                'channel' => null,
            ];
        }

        return $this->fulfillment->sendBilletNotification($participant, $payment, $forceResend);
    }

    /**
     * Accorde l'accès à la retraite (présence) et enregistre l'acteur.
     *
     * @param RetreatParticipant $participant Participant
     * @param User $actor Ouvrier ou administrateur
     * @return void
     */
    public function grantRetreatAccess(RetreatParticipant $participant, User $actor): void
    {
        if (! $participant->paiement_valide) {
            throw new \InvalidArgumentException('Le paiement doit être validé avant d\'accorder l\'accès.');
        }

        if ($participant->present) {
            throw new \InvalidArgumentException('L\'accès à la retraite a déjà été accordé.');
        }

        $participant->update([
            'present' => true,
            'date_presence' => $participant->date_presence ?? now(),
            'retreat_access_granted_by' => $actor->id,
        ]);
    }

    /**
     * Marque le badge physique comme remis.
     *
     * @param RetreatParticipant $participant Participant
     * @param User $actor Ouvrier ou administrateur
     * @return void
     */
    public function markBadgeReceived(RetreatParticipant $participant, User $actor): void
    {
        if (! $participant->paiement_valide) {
            throw new \InvalidArgumentException('Le badge ne peut être remis qu\'après validation du paiement.');
        }

        if ($participant->badge_received) {
            throw new \InvalidArgumentException('Le badge a déjà été remis à ce participant.');
        }

        if (! $participant->present) {
            throw new \InvalidArgumentException('Accordez d\'abord l\'accès à la retraite avant de remettre le badge.');
        }

        $participant->update([
            'badge_received' => true,
            'badge_received_at' => now(),
            'badge_received_by' => $actor->id,
        ]);
    }

    /**
     * @param RetreatParticipant $participant Participant
     * @return RetreatPayment|null Dernier paiement actif
     */
    protected function resolveLatestPayment(RetreatParticipant $participant): ?RetreatPayment
    {
        return $participant->payments()
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }
}
