<?php

namespace App\Models;

use App\Enums\EventAccessAuthMode;
use App\Enums\EventAccessOtpChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;
use Slimani\MediaManager\Concerns\InteractsWithMediaFiles;
use Wezlo\FilamentRecordWatcher\Concerns\HasWatchers;

/**
 * Événement (retraite, culte, etc.) — table `events_event`.
 *
 * Connexion portail : access_auth_mode (password|otp) et access_otp_channel (sms|email si otp).
 * Rôles admin : Spatie + Filament Shield ; fonction_metier sur User reste un libellé métier.
 */
class ChurchEvent extends Model
{
    use HasFactory;
    use HasWatchers;
    use InteractsWithMediaFiles;

    protected static function booted(): void
    {
        static::saving(function (ChurchEvent $event): void {
            if ($event->access_auth_mode === EventAccessAuthMode::Otp && ! $event->access_otp_channel) {
                $event->access_otp_channel = EventAccessOtpChannel::Email;
            }

            /*
             * Ne pas forcer is_active à false quand start_at est passé : cela désactivait
             * l’événement à chaque enregistrement (modif. prix, affiche, etc.).
             * Les inscriptions publiques se ferment via la date de fin (isOpenForPublicRetreatRegistration()).
             */
            if (! $event->is_active) {
                return;
            }

            $anotherActiveExists = self::query()
                ->where('is_active', true)
                ->whereKeyNot($event->getKey())
                ->exists();

            if ($anotherActiveExists) {
                throw ValidationException::withMessages([
                    'is_active' => "Impossible d'activer cet evenement: un autre evenement est deja actif.",
                ]);
            }
        });
    }

    /**
     * Inscriptions publiques closes : date de fin dépassée (pas la date de début).
     */
    public function isPublicRegistrationClosedBySchedule(): bool
    {
        if ($this->end_at === null) {
            return false;
        }

        return $this->end_at->isPast();
    }

    /**
     * Vrai si l’événement peut ouvrir le formulaire d’inscription publique (API / portail).
     */
    public function isOpenForPublicRetreatRegistration(): bool
    {
        return $this->type === 'retraite'
            && $this->is_active
            && ! $this->isPublicRegistrationClosedBySchedule();
    }

    /**
     * Événements retraite actifs dont les inscriptions en ligne sont encore ouvertes.
     *
     * @param Builder<ChurchEvent> $query
     * @return Builder<ChurchEvent>
     */
    public function scopeOpenForPublicRegistration(Builder $query): Builder
    {
        return $query
            ->where('type', 'retraite')
            ->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('end_at')->orWhere('end_at', '>', now());
            });
    }

    protected $table = 'events_event';

    protected $fillable = [
        'name',
        'type',
        'start_at',
        'end_at',
        'location',
        'affiche',
        'affiche_id',
        'capacity',
        'price_to_pay',
        'currency',
        'access_auth_mode',
        'access_otp_channel',
        'is_active',
    ];

    public function setAccessAuthModeAttribute(mixed $value): void
    {
        if ($value instanceof EventAccessAuthMode) {
            $value = $value->value;
        }

        $this->attributes['access_auth_mode'] = blank($value)
            ? EventAccessAuthMode::Password->value
            : (string) $value;
    }

    public function setAccessOtpChannelAttribute(mixed $value): void
    {
        if ($value instanceof EventAccessOtpChannel) {
            $value = $value->value;
        }

        $this->attributes['access_otp_channel'] = blank($value) ? null : (string) $value;
    }

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'capacity' => 'integer',
            'price_to_pay' => 'decimal:2',
            'is_active' => 'boolean',
            'access_auth_mode' => EventAccessAuthMode::class,
            'access_otp_channel' => EventAccessOtpChannel::class,
        ];
    }

    public function afficheMedia(): BelongsTo
    {
        return $this->mediaFile('affiche_id');
    }

    public function retreatDetail(): HasOne
    {
        return $this->hasOne(RetreatRetreatDetail::class, 'event_id');
    }
}
