<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetreatPolicyAcknowledgement extends Model
{
    use HasFactory;

    protected $table = 'retreat_policy_acknowledgements';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'has_read' => 'boolean',
            'has_accepted' => 'boolean',
            'acknowledged_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(RetreatPolicy::class, 'policy_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(RetreatParticipant::class, 'participant_id');
    }
}
