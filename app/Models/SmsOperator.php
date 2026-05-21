<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsOperator extends Model
{
    protected $fillable = [
        'name',
        'provider',
        'send_url',
        'balance_url',
        'delivery_url',
        'token',
        'sender',
        'send_method',
        'is_active',
        'remaining_sms',
        'last_balance_checked_at',
        'last_balance_response',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'remaining_sms' => 'integer',
            'last_balance_checked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (SmsOperator $operator): void {
            if (! $operator->is_active) {
                return;
            }

            static::query()
                ->whereKeyNot($operator->getKey())
                ->where('is_active', true)
                ->update(['is_active' => false]);
        });
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SmsMessageLog::class);
    }
}
