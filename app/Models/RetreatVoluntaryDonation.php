<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Don volontaire pour la retraite (nature ou espèces).
 */
class RetreatVoluntaryDonation extends Model
{
    public const KIND_IN_KIND = 'in_kind';

    public const KIND_CASH = 'cash';

    public const PURPOSE_GENERAL = 'general';

    public const PURPOSE_SPONSOR_YOUTH = 'sponsor_youth';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CASH_SUBMITTED = 'cash_submitted';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'reference',
        'donation_kind',
        'cash_purpose',
        'donor_name',
        'donor_phone',
        'donor_email',
        'in_kind_description',
        'youth_slots_count',
        'amount_expected',
        'amount_paid',
        'currency',
        'payment_channel',
        'payment_operator',
        'payment_phone',
        'provider_reference',
        'cash_proof_path',
        'cash_validated_at',
        'cash_validated_by',
        'status',
        'donor_message',
        'admin_notified',
        'donor_notified',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'youth_slots_count' => 'integer',
            'amount_expected' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'admin_notified' => 'boolean',
            'donor_notified' => 'boolean',
            'cash_validated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cashValidatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cash_validated_by');
    }

    /**
     * @return BelongsTo<ChurchEvent, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(ChurchEvent::class, 'event_id');
    }

    /**
     * @return HasMany<RetreatSponsorshipVoucher, $this>
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(RetreatSponsorshipVoucher::class, 'donation_id');
    }

    /**
     * Nombre total de places jeunes prévues pour ce don parrainage.
     *
     * @return int
     */
    public function sponsorshipSlotsTotal(): int
    {
        if ($this->cash_purpose !== self::PURPOSE_SPONSOR_YOUTH) {
            return 0;
        }

        $fromVouchers = $this->relationLoaded('vouchers')
            ? $this->vouchers->count()
            : $this->vouchers()->count();

        return max((int) ($this->youth_slots_count ?? 0), $fromVouchers);
    }

    /**
     * Nombre de places déjà utilisées via un code parrainage.
     *
     * @return int
     */
    public function sponsorshipSlotsUsed(): int
    {
        if ($this->cash_purpose !== self::PURPOSE_SPONSOR_YOUTH) {
            return 0;
        }

        if ($this->relationLoaded('vouchers')) {
            return $this->vouchers->whereNotNull('redeemed_by_participant_id')->count();
        }

        return $this->vouchers()->whereNotNull('redeemed_by_participant_id')->count();
    }

    /**
     * Places parrainage encore disponibles.
     *
     * @return int
     */
    public function sponsorshipSlotsRemaining(): int
    {
        return max(0, $this->sponsorshipSlotsTotal() - $this->sponsorshipSlotsUsed());
    }

    /**
     * Libellé de progression pour l'administration.
     *
     * @return string
     */
    public function sponsorshipProgressLabel(): string
    {
        $used = $this->sponsorshipSlotsUsed();
        $total = $this->sponsorshipSlotsTotal();

        return "{$used} / {$total} inscrit(s)";
    }

    /**
     * Résumé lisible du moyen de paiement pour l'administration.
     *
     * @return string
     */
    public function paymentDetailsSummary(): string
    {
        if ($this->donation_kind === self::KIND_IN_KIND) {
            return '—';
        }

        return match ($this->payment_channel) {
            'mobile_money' => trim(sprintf(
                'Mobile Money — %s%s',
                $this->payment_operator ?: 'Opérateur',
                filled($this->payment_phone) ? " · {$this->payment_phone}" : ''
            )),
            'card' => trim(sprintf(
                'Carte bancaire — %s',
                $this->payment_operator ?: 'FlexPay'
            ).(filled($this->provider_reference) ? " · ref. {$this->provider_reference}" : '')),
            'cash' => $this->payment_operator ?: 'Espèces (dépôt physique)',
            default => filled($this->payment_channel) ? (string) $this->payment_channel : 'Non renseigné',
        };
    }
}
