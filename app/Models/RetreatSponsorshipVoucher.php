<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Code parrainage généré après un don « places jeunes » — exempte le paiement à l'inscription.
 */
class RetreatSponsorshipVoucher extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'donation_id',
        'event_id',
        'code',
        'uses_total',
        'uses_remaining',
        'amount_covered',
        'currency',
        'redeemed_by_participant_id',
        'redeemed_at',
        'expires_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uses_total' => 'integer',
            'uses_remaining' => 'integer',
            'amount_covered' => 'decimal:2',
            'redeemed_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<RetreatVoluntaryDonation, $this>
     */
    public function donation(): BelongsTo
    {
        return $this->belongsTo(RetreatVoluntaryDonation::class, 'donation_id');
    }

    /**
     * @return BelongsTo<ChurchEvent, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(ChurchEvent::class, 'event_id');
    }

    /**
     * @return BelongsTo<RetreatParticipant, $this>
     */
    public function redeemedByParticipant(): BelongsTo
    {
        return $this->belongsTo(RetreatParticipant::class, 'redeemed_by_participant_id');
    }
}
