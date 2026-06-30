<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetreatChambre extends Model
{
    use HasFactory;

    protected $table = 'retreat_chambre';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'capacite' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ChurchEvent::class, 'event_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(RetreatParticipant::class, 'chambre_id');
    }
}
