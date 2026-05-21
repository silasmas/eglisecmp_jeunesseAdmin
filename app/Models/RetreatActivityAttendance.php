<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetreatActivityAttendance extends Model
{
    use HasFactory;

    protected $table = 'retreat_activity_attendances';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function activityPlan(): BelongsTo
    {
        return $this->belongsTo(RetreatActivityPlan::class, 'activity_plan_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(RetreatParticipant::class, 'participant_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
