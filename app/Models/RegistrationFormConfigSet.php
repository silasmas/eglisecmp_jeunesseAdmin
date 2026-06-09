<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wezlo\FilamentRecordWatcher\Concerns\HasWatchers;

/**
 * Jeu de configuration du formulaire d'inscription (modèle par défaut ou par événement).
 */
class RegistrationFormConfigSet extends Model
{
    use HasWatchers;

    protected $table = 'reg_form_config_sets';

    protected $fillable = [
        'church_event_id',
        'name',
        'is_published',
        'published_at',
        'ui_settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'ui_settings' => 'array',
        ];
    }

    /**
     * Événement associé (null = modèle par défaut).
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(ChurchEvent::class, 'church_event_id');
    }

    /**
     * Lignes de configuration par champ.
     */
    public function fieldItems(): HasMany
    {
        return $this->hasMany(RegistrationFormFieldItem::class, 'reg_form_config_set_id')
            ->orderBy('sort_order');
    }

    /**
     * Indique si ce jeu concerne le modèle global (sans événement).
     */
    public function isDefaultTemplate(): bool
    {
        return $this->church_event_id === null;
    }
}
