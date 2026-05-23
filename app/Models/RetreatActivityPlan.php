<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wezlo\FilamentRecordWatcher\Concerns\HasWatchers;

class RetreatActivityPlan extends Model
{
    use HasFactory, HasWatchers;

    protected $table = 'retreat_activity_plans';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime:H:i:s',
            'ends_at' => 'datetime:H:i:s',
            'is_mandatory' => 'boolean',
            'attendance_window_minutes' => 'integer',
            'attendance_reminder_sent_at' => 'datetime',
            'attendance_overdue_notified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(RetreatSession::class, 'session_id');
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RetreatActivityAttendance::class, 'activity_plan_id');
    }

    public function atelierReports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RetreatActivityAtelierReport::class, 'activity_plan_id');
    }
}
