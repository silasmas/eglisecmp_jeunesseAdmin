<?php

namespace App\Services\RetreatRegistration;

use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Models\RetreatSponsorshipVoucher;
use App\Models\RetreatVoluntaryDonation;
use Illuminate\Database\Eloquent\Builder;

/**
 * Calcule les places restantes en tenant compte des inscriptions,
 * des codes parrainage non utilisés et des sponsors en attente de paiement.
 */
class RetreatEventCapacityService
{
    /**
     * @param ChurchEvent $event Événement retraite
     * @return array{
     *   capacity: int|null,
     *   registered_participants: int,
     *   reserved_voucher_slots: int,
     *   pending_sponsor_slots: int,
     *   places_remaining: int|null,
     *   sponsor_slots_available: int|null,
     *   is_sold_out: bool,
     *   places_message: string|null
     * }
     */
    public function snapshot(ChurchEvent $event, ?int $excludeDonationId = null): array
    {
        $capacity = $event->capacity ? (int) $event->capacity : null;
        $registered = $this->countRegisteredParticipants($event->id);
        $reservedVouchers = $this->countReservedVoucherSlots($event->id);
        $pendingSponsor = $this->countPendingSponsorSlots($event->id, $excludeDonationId);
        $committedForSponsor = $registered + $reservedVouchers + $pendingSponsor;
        $sponsorRemaining = ($capacity !== null && $capacity > 0)
            ? max(0, $capacity - $committedForSponsor)
            : null;

        $registrationRemaining = ($capacity !== null && $capacity > 0)
            ? max(0, $capacity - $registered)
            : null;

        $isSoldOut = $registrationRemaining !== null && $registrationRemaining === 0;

        return [
            'capacity' => $capacity,
            'registered_participants' => $registered,
            'reserved_voucher_slots' => $reservedVouchers,
            'pending_sponsor_slots' => $pendingSponsor,
            'places_remaining' => $sponsorRemaining,
            'sponsor_slots_available' => $sponsorRemaining,
            'registration_slots_available' => $registrationRemaining,
            'is_sold_out' => $isSoldOut,
            'places_message' => $this->buildPlacesMessage($capacity, $registrationRemaining),
        ];
    }

    /**
     * Vérifie qu'un nombre de places sponsor est disponible.
     *
     * @param ChurchEvent $event Événement cible
     * @param int $requestedSlots Nombre de jeunes à sponsoriser
     * @return string|null Message d'erreur ou null si OK
     */
    public function sponsorSlotsError(ChurchEvent $event, int $requestedSlots, ?int $excludeDonationId = null): ?string
    {
        $requested = max(1, $requestedSlots);
        $snapshot = $this->snapshot($event, $excludeDonationId);
        $available = $snapshot['sponsor_slots_available'];

        if ($available === null) {
            return null;
        }

        if ($requested > $available) {
            return $available === 0
                ? 'Aucune place disponible pour sponsoriser des jeunes : la capacité de la retraite est atteinte.'
                : "Vous ne pouvez sponsoriser que {$available} jeune".($available > 1 ? 's' : '').' (places restantes pour la retraite).';
        }

        return null;
    }

    /**
     * Message affiché lorsque les inscriptions publiques sont closes.
     *
     * @param ChurchEvent $event Événement cible
     * @return string|null
     */
    public function registrationClosedMessage(ChurchEvent $event): ?string
    {
        $capacity = $event->capacity ? (int) $event->capacity : null;
        if (! $capacity || $capacity < 1) {
            return null;
        }

        if ($this->countRegisteredParticipants($event->id) >= $capacity) {
            return 'Le nombre maximal de participants pour cette retraite est atteint.';
        }

        return null;
    }

    /**
     * @param int $eventId Identifiant événement
     * @return int
     */
    public function countRegisteredParticipants(int $eventId): int
    {
        return (int) RetreatParticipant::query()
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->where('role_participant', 'participant')
                    ->orWhereNull('role_participant')
                    ->orWhere('role_participant', '');
            })
            ->count();
    }

    /**
     * @param int $eventId Identifiant événement
     * @return int
     */
    protected function countReservedVoucherSlots(int $eventId): int
    {
        return (int) RetreatSponsorshipVoucher::query()
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->where('uses_remaining', '>', 0)
            ->sum('uses_remaining');
    }

    /**
     * @param int $eventId Identifiant événement
     * @return int
     */
    protected function countPendingSponsorSlots(int $eventId, ?int $excludeDonationId = null): int
    {
        return (int) RetreatVoluntaryDonation::query()
            ->where('event_id', $eventId)
            ->where('donation_kind', RetreatVoluntaryDonation::KIND_CASH)
            ->where('cash_purpose', RetreatVoluntaryDonation::PURPOSE_SPONSOR_YOUTH)
            ->whereIn('status', [
                RetreatVoluntaryDonation::STATUS_PENDING,
                RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED,
            ])
            ->when($excludeDonationId, fn ($query) => $query->where('id', '!=', $excludeDonationId))
            ->sum('youth_slots_count');
    }

    /**
     * @param int|null $capacity Capacité maximale
     * @param int|null $remaining Places restantes
     * @return string|null
     */
    protected function buildPlacesMessage(?int $capacity, ?int $remaining): ?string
    {
        if ($remaining === null) {
            return null;
        }

        if ($remaining === 0) {
            return 'Toutes les places sont occupées pour cette retraite.';
        }

        $suffix = $capacity ? " sur {$capacity}." : '.';

        return "Il reste {$remaining} place".($remaining > 1 ? 's' : '').$suffix;
    }
}
