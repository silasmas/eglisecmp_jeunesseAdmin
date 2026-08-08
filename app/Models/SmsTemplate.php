<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Modèle SMS avec corps dynamique (variables {{prenom}}, {{lien_billet}}, etc.).
 */
class SmsTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'body',
        'description',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Génère un slug unique à partir du nom si absent.
     */
    protected static function booted(): void
    {
        static::saving(function (SmsTemplate $template): void {
            if (filled($template->slug)) {
                return;
            }

            $base = Str::slug((string) $template->name) ?: 'modele-sms';
            $slug = $base;
            $suffix = 1;

            while (static::query()
                ->where('slug', $slug)
                ->when($template->exists, fn (Builder $q) => $q->whereKeyNot($template->getKey()))
                ->exists()
            ) {
                $slug = $base.'-'.$suffix;
                $suffix++;
            }

            $template->slug = $slug;
        });
    }

    /**
     * @param  Builder<SmsTemplate>  $query
     * @return Builder<SmsTemplate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
