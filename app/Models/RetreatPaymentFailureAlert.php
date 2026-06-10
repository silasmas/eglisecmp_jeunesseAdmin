<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alerte enregistrée lors d'un échec de paiement d'inscription retraite.
 */
class RetreatPaymentFailureAlert extends Model
{
    protected $table = 'retreat_payment_failure_alerts';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'technical_detail' => 'array',
            'email_sent_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<RetreatPayment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(RetreatPayment::class, 'retreat_payment_id');
    }

    /**
     * @return BelongsTo<RetreatParticipant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(RetreatParticipant::class, 'participant_id');
    }

    /**
     * @return BelongsTo<ChurchEvent, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(ChurchEvent::class, 'event_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * @return bool Indique si l'alerte a été traitée
     */
    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }
}
