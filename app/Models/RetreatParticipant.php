<?php

namespace App\Models;

use App\Support\AvatarFallback;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Wezlo\FilamentRecordWatcher\Concerns\HasWatchers;

class RetreatParticipant extends Model implements HasAvatar, HasName
{
    use HasFactory, HasWatchers;

    protected $table = 'retreat_participant';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'paiement_valide' => 'boolean',
            'present' => 'boolean',
            'billet_envoye' => 'boolean',
            'is_verified' => 'boolean',
            'billet_envoye_email' => 'boolean',
            'billet_envoye_whatsapp' => 'boolean',
            'is_active' => 'boolean',
            'date_presence' => 'datetime',
            'date_billet_envoye' => 'datetime',
            'exit_allowed' => 'boolean',
            'curfew_time' => 'string',
            'registration_otp_sent_at' => 'datetime',
            'registration_otp_expires_at' => 'datetime',
            'registration_otp_verified_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(RetreatAtelier::class, 'atelier_id');
    }

    public function chambre(): BelongsTo
    {
        return $this->belongsTo(RetreatChambre::class, 'chambre_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RetreatPayment::class, 'participant_id');
    }

    public function activityAttendances(): HasMany
    {
        return $this->hasMany(RetreatActivityAttendance::class, 'participant_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(RetreatParticipantMovement::class, 'participant_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ChurchEvent::class, 'event_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->prenom ?? '').' '.($this->nom ?? ''));
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (blank($this->photo)) {
            return AvatarFallback::url();
        }

        if (Str::startsWith($this->photo, ['http://', 'https://', '/', 'data:image/'])) {
            return $this->photo;
        }

        return Storage::url($this->photo);
    }
}
