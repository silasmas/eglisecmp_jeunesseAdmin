<?php

namespace App\Models;

use App\Enums\ChurchEventType;
use App\Enums\EventAccessAuthMode;
use App\Enums\EventAccessOtpChannel;
use App\Services\RetreatEventLogisticsLifecycleService;
use App\Support\ChurchEventPublicRegistrationEvaluator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

        static::updated(function (ChurchEvent $event): void {
            if (! $event->wasChanged('is_publicly_closed') || ! $event->is_publicly_closed) {
                return;
            }

            app(RetreatEventLogisticsLifecycleService::class)->deactivateForEvent($event);
        });
    }

    /**
     * Retraite archivée (hors opérations courantes, visible dans l'historique).
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @param Builder<ChurchEvent> $query
     * @return Builder<ChurchEvent>
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @param Builder<ChurchEvent> $query
     * @return Builder<ChurchEvent>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Retraite terminée selon le planning (date de fin dépassée).
     */
    public function isSchedulePast(): bool
    {
        if ($this->end_at === null) {
            return false;
        }

        return $this->end_at->isPast();
    }

    /**
     * Accès public fermé manuellement par l'administration.
     */
    public function isPublicPortalClosed(): bool
    {
        return (bool) $this->is_publicly_closed;
    }

    /**
     * Inscriptions publiques closes : fenêtre d'inscription dépassée ou date de fin événement.
     */
    public function isPublicRegistrationClosedBySchedule(): bool
    {
        $closesAt = ChurchEventPublicRegistrationEvaluator::resolveRegistrationClosesAt($this);

        if ($closesAt === null) {
            return false;
        }

        return $closesAt->isPast();
    }

    /**
     * Inscriptions pas encore ouvertes (date de début d'inscription future).
     */
    public function isPublicRegistrationNotYetOpen(): bool
    {
        if ($this->public_registration_opens_at === null) {
            return false;
        }

        return $this->public_registration_opens_at->isFuture();
    }

    /**
     * Vrai si l'événement peut ouvrir le formulaire d'inscription publique (API / portail).
     */
    public function isOpenForPublicRetreatRegistration(): bool
    {
        return ChurchEventPublicRegistrationEvaluator::isOpen($this);
    }

    /**
     * Événements retraite dont les inscriptions en ligne sont ouvertes.
     *
     * @param Builder<ChurchEvent> $query
     * @return Builder<ChurchEvent>
     */
    public function scopeOpenForPublicRegistration(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('type', ChurchEventType::Retraite->value)
            ->where('is_active', true)
            ->where('is_publicly_closed', false)
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('public_registration_opens_at')
                    ->orWhere('public_registration_opens_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now): void {
                $q->where(function (Builder $inner) use ($now): void {
                    $inner->whereNotNull('public_registration_closes_at')
                        ->where('public_registration_closes_at', '>', $now);
                })->orWhere(function (Builder $inner) use ($now): void {
                    $inner->whereNull('public_registration_closes_at')
                        ->where(function (Builder $fallback) use ($now): void {
                            $fallback->whereNull('end_at')->orWhere('end_at', '>', $now);
                        });
                });
            });
    }

    /**
     * Événements retraite actifs ouverts aux dons (y compris retraite clôturée côté public).
     *
     * @param Builder<ChurchEvent> $query
     * @return Builder<ChurchEvent>
     */
    public function scopeAvailableForDonations(Builder $query): Builder
    {
        return $query->where('type', ChurchEventType::Retraite->value);
    }

    /**
     * Retraite utilisée pour le portail don (active en priorité, sinon dernière retraite).
     *
     * @return ChurchEvent|null
     */
    public static function resolveForDonPortal(): ?self
    {
        return self::query()
            ->availableForDonations()
            ->orderByDesc('is_active')
            ->orderByDesc('start_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Message affiché lorsque la prise en charge jeunes est indisponible.
     *
     * @return string|null Null si la prise en charge reste possible
     */
    public function sponsorshipDonDisabledReason(): ?string
    {
        if (! $this->isPublicPortalClosed()) {
            return null;
        }

        return sprintf(
            'La prise en charge de jeunes n\'est plus disponible : la retraite « %s » est clôturée. Les inscriptions étant terminées, les codes parrainage ne peuvent plus être utilisés. Vous pouvez toujours faire un don pour le bon fonctionnement.',
            $this->name
        );
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
        'document_reglement',
        'document_histoires',
        'capacity',
        'price_to_pay',
        'currency',
        'access_auth_mode',
        'access_otp_channel',
        'is_active',
        'is_publicly_closed',
        'archived_at',
        'public_registration_opens_at',
        'public_registration_closes_at',
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
            'public_registration_opens_at' => 'datetime',
            'public_registration_closes_at' => 'datetime',
            'capacity' => 'integer',
            'price_to_pay' => 'decimal:2',
            'is_active' => 'boolean',
            'is_publicly_closed' => 'boolean',
            'archived_at' => 'datetime',
            'access_auth_mode' => EventAccessAuthMode::class,
            'access_otp_channel' => EventAccessOtpChannel::class,
        ];
    }

    public function afficheMedia(): BelongsTo
    {
        return $this->mediaFile('affiche_id');
    }

    /**
     * Participants inscrits à cet événement.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(RetreatParticipant::class, 'event_id');
    }

    public function retreatDetail(): HasOne
    {
        return $this->hasOne(RetreatRetreatDetail::class, 'event_id');
    }

    /**
     * Jeu de configuration du formulaire d'inscription lié à cet événement.
     */
    public function registrationFormConfigSet(): HasOne
    {
        return $this->hasOne(RegistrationFormConfigSet::class, 'church_event_id');
    }
}
