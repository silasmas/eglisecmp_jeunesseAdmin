<?php

namespace App\Models;

use App\Support\AvatarFallback;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nom',
        'postnom',
        'prenom',
        'sexe',
        'date_naissance',
        'email',
        'indicatif_telephone',
        'telephone',
        'telephone_urgence',
        'guardian_name',
        'guardian_phone',
        'adresse',
        'commune',
        'ville',
        'eglise_assemblee',
        'departement_cellule',
        'hebergement_choice',
        'observation',
        'password',
        'last_login',
        'is_active',
        'fonction_metier',
        'role_jeunesse',
        'owner_id',
        'profile_photo_path',
        'role_participant',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_naissance' => 'date',
            'last_login' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Accès au panel Filament : compte actif.
     * Les rôles / permissions Shield (Spatie) contrôlent les actions dans le panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_active;
    }

    public function getFilamentName(): string
    {
        return (string) $this->name;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (blank($this->profile_photo_path)) {
            return AvatarFallback::url();
        }

        if (Str::startsWith($this->profile_photo_path, ['http://', 'https://', '/', 'data:image/'])) {
            return $this->profile_photo_path;
        }

        return app(\App\Services\PublicStorageUrl::class)->fromPath($this->profile_photo_path);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(self::class, 'owner_id');
    }

    public function chambresResponsable(): HasMany
    {
        return $this->hasMany(RetreatChambre::class, 'responsable_user_id');
    }

    public function ateliersResponsable(): HasMany
    {
        return $this->hasMany(RetreatAtelier::class, 'responsable_user_id');
    }
}
