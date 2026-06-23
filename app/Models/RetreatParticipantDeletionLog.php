<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historique d'une suppression de participant(s) retraite par un administrateur.
 */
class RetreatParticipantDeletionLog extends Model
{
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
