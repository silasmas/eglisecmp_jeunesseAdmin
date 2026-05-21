<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetreatParticipantMovement extends Model
{
    use HasFactory;

    protected $table = 'retreat_participant_movements';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'moved_at' => 'datetime',
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

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
