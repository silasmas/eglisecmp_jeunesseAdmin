<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compte-rendu d'une activité pour un atelier (sujet, texte biblique, conducteurs, résumé).
 */
class RetreatActivityAtelierReport extends Model
{
    use HasFactory;

    protected $table = 'retreat_activity_atelier_reports';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conducteurs' => 'array',
            'is_active' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Indique si le compte-rendu a été soumis définitivement (non modifiable par l'encadreur).
     */
    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function activityPlan(): BelongsTo
    {
        return $this->belongsTo(RetreatActivityPlan::class, 'activity_plan_id');
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(RetreatAtelier::class, 'atelier_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
