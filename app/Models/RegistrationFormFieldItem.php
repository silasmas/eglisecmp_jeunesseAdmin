<?php

namespace App\Models;

use App\Enums\RegistrationFormColumnSpan;
use App\Enums\RegistrationFormFieldKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuration d'un champ du formulaire d'inscription pour un jeu donné.
 */
class RegistrationFormFieldItem extends Model
{
    protected $table = 'reg_form_field_items';

    protected $fillable = [
        'reg_form_config_set_id',
        'field_key',
        'is_visible',
        'is_required',
        'column_span',
        'is_admin_unlocked',
        'label_override',
        'helper_text_override',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_required' => 'boolean',
            'is_admin_unlocked' => 'boolean',
            'column_span' => RegistrationFormColumnSpan::class,
            'field_key' => RegistrationFormFieldKey::class,
        ];
    }

    /**
     * Jeu parent de configuration.
     */
    public function configSet(): BelongsTo
    {
        return $this->belongsTo(RegistrationFormConfigSet::class, 'reg_form_config_set_id');
    }
}
