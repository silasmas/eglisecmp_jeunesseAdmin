<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * QR code généré depuis l'administration (lien + logo au centre optionnel).
 */
class GeneratedQrCode extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'title',
        'target_url',
        'embed_logo',
        'logo_key',
        'file_path',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'embed_logo' => 'boolean',
        ];
    }

    /**
     * Utilisateur créateur du QR code.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * URL publique du PNG généré (disque public).
     *
     * @return string|null URL ou null si non généré
     */
    public function publicImageUrl(): ?string
    {
        if (! filled($this->file_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->file_path);
    }
}
