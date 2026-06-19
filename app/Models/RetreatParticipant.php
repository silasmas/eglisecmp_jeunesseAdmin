<?php

namespace App\Models;

use App\Support\AvatarFallback;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
            'badge_received' => 'boolean',
            'badge_received_at' => 'datetime',
            'is_active' => 'boolean',
            'date_presence' => 'datetime',
            'date_billet_envoye' => 'datetime',
            'exit_allowed' => 'boolean',
            'curfew_time' => 'string',
            'registration_otp_sent_at' => 'datetime',
            'registration_otp_expires_at' => 'datetime',
            'registration_otp_verified_at' => 'datetime',
            'inscription_funnel_at' => 'datetime',
            'atelier_quarantine' => 'boolean',
            'atelier_quarantine_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Utilisateur ayant accordé l'accès à la retraite (scan / portail).
     */
    public function retreatAccessGrantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retreat_access_granted_by');
    }

    /**
     * Utilisateur ayant remis le badge physique.
     */
    public function badgeReceivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'badge_received_by');
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

    /**
     * Dernier enregistrement de paiement (tous canaux).
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(RetreatPayment::class, 'participant_id')->latestOfMany('id');
    }

    /**
     * Dernier paiement cash du participant (validation admin).
     */
    public function latestCashPayment(): HasOne
    {
        return $this->hasOne(RetreatPayment::class, 'participant_id')
            ->where('channel', 'cash')
            ->latestOfMany('id');
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

    /**
     * Code parrainage utilisé pour couvrir l'inscription de ce participant.
     *
     * @return HasOne<RetreatSponsorshipVoucher, $this>
     */
    public function sponsorshipVoucher(): HasOne
    {
        return $this->hasOne(RetreatSponsorshipVoucher::class, 'redeemed_by_participant_id');
    }

    /**
     * Indique si l'inscription a été couverte par un code de prise en charge.
     *
     * @return bool
     */
    public function isSponsoredRegistration(): bool
    {
        if ($this->relationLoaded('sponsorshipVoucher')) {
            return $this->sponsorshipVoucher !== null;
        }

        return $this->sponsorshipVoucher()->exists();
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

        return app(\App\Services\PublicStorageUrl::class)->fromPath($this->photo);
    }

    /**
     * Libellé chambre affiché sur billet, e-mail et portail public.
     *
     * @return string
     */
    public function placementChambreLabel(): string
    {
        if ($this->chambre && filled($this->chambre->nom)) {
            return (string) $this->chambre->nom;
        }

        $type = strtolower(trim((string) $this->participant_type));

        if (in_array($type, ['external', 'externe'], true)) {
            return 'Non applicable (hébergement externe)';
        }

        return 'Non assignée';
    }

    /**
     * Libellé atelier affiché sur billet, e-mail et portail public.
     *
     * @return string
     */
    public function placementAtelierLabel(): string
    {
        if ($this->atelier && filled($this->atelier->numero)) {
            return 'Atelier '.$this->atelier->numero;
        }

        return 'Non assigné';
    }
}
