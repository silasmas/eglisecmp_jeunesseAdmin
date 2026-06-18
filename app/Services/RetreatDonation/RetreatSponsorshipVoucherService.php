<?php

namespace App\Services\RetreatDonation;

use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Models\RetreatSponsorshipVoucher;
use App\Models\RetreatVoluntaryDonation;
use App\Services\RetreatRegistrationFulfillmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Génération, validation et utilisation des codes parrainage jeunes.
 */
class RetreatSponsorshipVoucherService
{
    public function __construct(
        protected RetreatRegistrationFulfillmentService $fulfillment,
    ) {}

    /**
     * Crée N codes uniques après un don sponsor jeunes payé.
     *
     * @param RetreatVoluntaryDonation $donation Don parent payé
     * @param ChurchEvent $event Événement cible
     * @return list<RetreatSponsorshipVoucher>
     */
    public function generateForDonation(RetreatVoluntaryDonation $donation, ChurchEvent $event): array
    {
        $count = max(1, (int) $donation->youth_slots_count);
        $amount = (float) ($event->price_to_pay ?? 0);
        $currency = (string) ($event->currency ?? 'USD');
        $expiresAt = $event->end_at;

        $created = [];

        for ($i = 0; $i < $count; $i++) {
            $created[] = RetreatSponsorshipVoucher::query()->create([
                'donation_id' => $donation->id,
                'event_id' => $event->id,
                'code' => $this->generateUniqueCode(),
                'uses_total' => 1,
                'uses_remaining' => 1,
                'amount_covered' => $amount,
                'currency' => $currency,
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]);
        }

        return $created;
    }

    /**
     * @return string Code unique RET-SP-XXXXXX
     */
    public function generateUniqueCode(): string
    {
        do {
            $code = 'RET-SP-'.Str::upper(Str::random(6));
        } while (RetreatSponsorshipVoucher::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Trouve un voucher utilisable pour l'événement.
     *
     * @param string $code Code saisi
     * @param int $eventId Identifiant événement
     * @return RetreatSponsorshipVoucher|null
     */
    public function findRedeemable(string $code, int $eventId): ?RetreatSponsorshipVoucher
    {
        $normalized = Str::upper(trim($code));

        if ($normalized === '') {
            return null;
        }

        $voucher = RetreatSponsorshipVoucher::query()
            ->where('code', $normalized)
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->where('uses_remaining', '>', 0)
            ->first();

        if (! $voucher) {
            return null;
        }

        if ($voucher->expires_at && now()->gt($voucher->expires_at)) {
            return null;
        }

        return $voucher;
    }

    /**
     * Utilise le code pour un participant : décrémente et marque le paiement comme couvert.
     *
     * @param RetreatSponsorshipVoucher $voucher Code valide
     * @param RetreatParticipant $participant Participant inscrit
     * @return void
     */
    public function redeemForParticipant(RetreatSponsorshipVoucher $voucher, RetreatParticipant $participant): void
    {
        DB::transaction(function () use ($voucher, $participant): void {
            $locked = RetreatSponsorshipVoucher::query()
                ->whereKey($voucher->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->uses_remaining < 1 || ! $locked->is_active) {
                throw new \RuntimeException('Ce code parrainage n’est plus disponible.');
            }

            $locked->update([
                'uses_remaining' => $locked->uses_remaining - 1,
                'redeemed_by_participant_id' => $participant->id,
                'redeemed_at' => now(),
            ]);

            $payment = RetreatPayment::query()->firstOrNew([
                'participant_id' => $participant->id,
                'event_id' => $participant->event_id,
            ]);

            if (! $payment->exists) {
                $payment->reference = 'RET-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5));
            }

            $payment->fill([
                'amount_expected' => $locked->amount_covered,
                'amount_paid' => $locked->amount_covered,
                'currency' => $locked->currency,
                'channel' => 'sponsorship_voucher',
                'etat' => 'payee',
                'access_granted' => true,
                'access_granted_at' => now(),
                'provider_message' => 'Couvert par code parrainage '.$locked->code,
            ]);
            $payment->paid_at = now();
            $payment->save();

            $participant->update([
                'paiement_valide' => true,
                'registration_status' => 'confirmed',
            ]);

            $this->fulfillment->fulfillIfNeeded($payment->fresh(['participant', 'event']));
        });
    }
}
