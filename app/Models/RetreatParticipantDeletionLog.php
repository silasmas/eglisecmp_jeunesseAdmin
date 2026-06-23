<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historique d'une suppression de participant(s) retraite par un administrateur.
 */
class RetreatParticipantDeletionLog extends Model
{
    /**
     * Délai minimum de conservation avant purge de l'historique (en mois).
     */
    public const RETENTION_MONTHS = 1;

    /** @var list<string> */
    protected $fillable = [
        'performed_by',
        'event_id',
        'participant_count',
        'participants_summary',
        'related_summary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'participant_count' => 'integer',
        ];
    }

    /**
     * Indique si l'entrée d'historique peut être supprimée (âge >= 1 mois).
     *
     * @return bool
     */
    public function isPurgeable(): bool
    {
        $purgeableAt = $this->purgeableAt();

        if ($purgeableAt === null) {
            return false;
        }

        return now()->gte($purgeableAt);
    }

    /**
     * Date à partir de laquelle l'entrée peut être purgée.
     *
     * @return CarbonInterface|null
     */
    public function purgeableAt(): ?CarbonInterface
    {
        return $this->created_at?->copy()->addMonths(self::RETENTION_MONTHS);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * @return BelongsTo<ChurchEvent, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(ChurchEvent::class, 'event_id');
    }
}
