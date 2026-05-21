<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wezlo\FilamentRecordWatcher\Concerns\HasWatchers;

class RetreatPayment extends Model
{
    use HasFactory, HasWatchers;

    protected $table = 'retreat_payments';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount_expected' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'access_granted' => 'boolean',
            'access_granted_at' => 'datetime',
            'paid_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(RetreatParticipant::class, 'participant_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ChurchEvent::class, 'event_id');
    }

    public function accessGrantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'access_granted_by');
    }
}
