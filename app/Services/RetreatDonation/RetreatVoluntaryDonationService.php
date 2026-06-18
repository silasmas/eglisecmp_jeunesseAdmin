<?php

namespace App\Services\RetreatDonation;

use App\Models\ChurchEvent;
use App\Models\RetreatVoluntaryDonation;
use App\Models\User;
use App\Services\RetreatRegistration\RetreatEventCapacityService;
use Illuminate\Support\Str;

/**
 * Création et finalisation des dons volontaires retraite.
 */
class RetreatVoluntaryDonationService
{
    public function __construct(
        protected RetreatVoluntaryDonationNotifier $notifier,
        protected RetreatSponsorshipVoucherService $voucherService,
        protected RetreatEventCapacityService $capacityService,
    ) {}

    /**
     * @return string Référence unique DON-RET-…
     */
    public function generateReference(): string
    {
        do {
            $reference = 'DON-RET-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5));
        } while (RetreatVoluntaryDonation::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Enregistre un don en nature et notifie les super_admin.
     *
     * @param ChurchEvent $event Retraite active
     * @param array<string, mixed> $data Données validées
     * @return RetreatVoluntaryDonation
     */
    public function createInKind(ChurchEvent $event, array $data): RetreatVoluntaryDonation
    {
        $donation = RetreatVoluntaryDonation::query()->create([
            'event_id' => $event->id,
            'reference' => $this->generateReference(),
            'donation_kind' => RetreatVoluntaryDonation::KIND_IN_KIND,
            'donor_name' => $data['donor_name'],
            'donor_phone' => $data['donor_phone'] ?? null,
            'donor_email' => isset($data['donor_email']) ? Str::lower(trim((string) $data['donor_email'])) : null,
            'in_kind_description' => $data['in_kind_description'],
            'donor_message' => $data['donor_message'] ?? null,
            'status' => RetreatVoluntaryDonation::STATUS_SUBMITTED,
            'currency' => (string) ($event->currency ?? 'USD'),
        ]);

        $this->notifier->notifySuperAdmins($donation->fresh(['event']));
        $this->notifier->notifyDonor($donation->fresh(['event']));

        return $donation;
    }

    /**
     * Prépare un don en espèces avant paiement FlexPay.
     *
     * @param ChurchEvent $event Retraite active
     * @param array<string, mixed> $data Données validées
     * @return RetreatVoluntaryDonation
     */
    public function createCashPending(ChurchEvent $event, array $data): RetreatVoluntaryDonation
    {
        $purpose = (string) $data['cash_purpose'];
        $unitPrice = (float) ($event->price_to_pay ?? 0);
        $currency = (string) ($event->currency ?? 'USD');

        if ($purpose === RetreatVoluntaryDonation::PURPOSE_SPONSOR_YOUTH) {
            $slots = max(1, (int) $data['youth_slots_count']);
            $capacityError = $this->capacityService->sponsorSlotsError($event, $slots);
            if ($capacityError !== null) {
                throw new \InvalidArgumentException($capacityError);
            }
            $amount = round($slots * $unitPrice, 2);
        } else {
            $slots = null;
            $amount = round((float) $data['amount'], 2);
        }

        return RetreatVoluntaryDonation::query()->create([
            'event_id' => $event->id,
            'reference' => $this->generateReference(),
            'donation_kind' => RetreatVoluntaryDonation::KIND_CASH,
            'cash_purpose' => $purpose,
            'donor_name' => $data['donor_name'],
            'donor_phone' => $data['donor_phone'] ?? null,
            'donor_email' => isset($data['donor_email']) ? Str::lower(trim((string) $data['donor_email'])) : null,
            'youth_slots_count' => $slots,
            'amount_expected' => $amount,
            'amount_paid' => 0,
            'currency' => $currency,
            'donor_message' => $data['donor_message'] ?? null,
            'status' => RetreatVoluntaryDonation::STATUS_PENDING,
        ]);
    }

    /**
     * Enregistre une preuve de paiement cash — validation admin requise avant codes parrainage.
     *
     * @param RetreatVoluntaryDonation $donation Don en attente
     * @param string $proofPath Chemin fichier preuve
     * @return RetreatVoluntaryDonation
     */
    public function submitCashProof(RetreatVoluntaryDonation $donation, string $proofPath): RetreatVoluntaryDonation
    {
        if ($donation->status === RetreatVoluntaryDonation::STATUS_PAID) {
            return $donation;
        }

        $donation->update([
            'cash_proof_path' => $proofPath,
            'payment_channel' => 'cash',
            'status' => RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED,
        ]);

        $this->notifier->notifyCashPendingAdmins($donation->fresh(['event']));
        $this->notifier->notifyDonorCashSubmitted($donation->fresh(['event']));

        return $donation->fresh(['event']);
    }

    /**
     * Valide un don cash par l'admin : marque payé et génère les codes si sponsor.
     *
     * @param RetreatVoluntaryDonation $donation Don avec preuve
     * @param User $admin Administrateur validateur
     * @return RetreatVoluntaryDonation
     */
    public function approveCashPayment(RetreatVoluntaryDonation $donation, User $admin): RetreatVoluntaryDonation
    {
        if ($donation->status === RetreatVoluntaryDonation::STATUS_PAID) {
            return $donation;
        }

        $donation->update([
            'cash_validated_at' => now(),
            'cash_validated_by' => $admin->id,
        ]);

        return $this->markCashPaid(
            $donation->fresh(),
            (float) $donation->amount_expected,
            'cash',
            null
        );
    }

    /**
     * Rejette un don cash soumis.
     *
     * @param RetreatVoluntaryDonation $donation Don cible
     * @param User $admin Administrateur
     * @param string|null $reason Motif optionnel
     * @return RetreatVoluntaryDonation
     */
    public function rejectCashPayment(RetreatVoluntaryDonation $donation, User $admin, ?string $reason = null): RetreatVoluntaryDonation
    {
        $donation->update([
            'status' => RetreatVoluntaryDonation::STATUS_CANCELLED,
            'cash_validated_at' => now(),
            'cash_validated_by' => $admin->id,
        ]);

        return $donation->fresh();
    }

    /**
     * Marque un don cash comme payé, génère les codes si besoin, notifie les super_admin.
     *
     * @param RetreatVoluntaryDonation $donation Don en attente
     * @param float $amountPaid Montant confirmé
     * @param string|null $channel Canal de paiement
     * @param string|null $providerReference Référence opérateur
     * @return RetreatVoluntaryDonation
     */
    public function markCashPaid(
        RetreatVoluntaryDonation $donation,
        float $amountPaid,
        ?string $channel = null,
        ?string $providerReference = null,
    ): RetreatVoluntaryDonation {
        if ($donation->status === RetreatVoluntaryDonation::STATUS_PAID) {
            return $donation;
        }

        $donation->update([
            'amount_paid' => $amountPaid,
            'payment_channel' => $channel,
            'provider_reference' => $providerReference,
            'status' => RetreatVoluntaryDonation::STATUS_PAID,
        ]);

        $donation->load('event');

        if (
            $donation->cash_purpose === RetreatVoluntaryDonation::PURPOSE_SPONSOR_YOUTH
            && $donation->event instanceof ChurchEvent
        ) {
            $capacityError = $this->capacityService->sponsorSlotsError(
                $donation->event,
                (int) $donation->youth_slots_count,
                $donation->id
            );
            if ($capacityError !== null) {
                throw new \RuntimeException($capacityError);
            }

            $this->voucherService->generateForDonation($donation, $donation->event);
        }

        $this->notifier->notifySuperAdmins($donation->fresh(['event', 'vouchers']));
        $this->notifier->notifyDonor($donation->fresh(['event']));

        return $donation;
    }
}
