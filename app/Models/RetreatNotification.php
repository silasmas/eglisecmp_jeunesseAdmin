<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RetreatNotification extends Model
{
    use HasFactory;

    protected $table = 'retreat_notification';

    protected $fillable = [
        'title',
        'message',
        'link',
        'is_read',
        'user_id',
        'is_active',
        'category',
        'subject_type',
        'subject_id',
        'laravel_notification_id',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
