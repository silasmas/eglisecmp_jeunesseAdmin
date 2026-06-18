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
}
