<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wezlo\FilamentRecordWatcher\Concerns\HasWatchers;

class RetreatSession extends Model
{
    use HasFactory, HasWatchers;

    protected $table = 'retreat_session';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ChurchEvent::class, 'event_id');
    }
}
