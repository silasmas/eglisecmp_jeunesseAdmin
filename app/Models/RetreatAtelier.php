<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetreatAtelier extends Model
{
    use HasFactory;

    protected $table = 'retreat_atelier';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }

    public function adjoint(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjoint_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(RetreatParticipant::class, 'atelier_id');
    }
}
