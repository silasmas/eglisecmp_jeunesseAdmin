<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessageLog extends Model
{
    protected $fillable = [
        'sms_operator_id',
        'provider',
        'context',
        'sender',
        'recipient',
        'message',
        'status',
        'delivery_status',
        'http_method',
        'http_status',
        'provider_reference',
        'provider_response',
        'delivery_response',
        'error_message',
        'sent_at',
        'delivery_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivery_checked_at' => 'datetime',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(SmsOperator::class, 'sms_operator_id');
    }
}
