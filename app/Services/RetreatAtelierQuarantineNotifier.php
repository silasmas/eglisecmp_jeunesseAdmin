<?php

namespace App\Services;

use App\Filament\Pages\ManageRetreatAtelierQuarantine;
use App\Filament\Resources\RetreatParticipants\RetreatParticipantResource;
use App\Models\RetreatParticipant;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Notifie les super_admin lorsqu'un participant est en quarantaine atelier.
 */
class RetreatAtelierQuarantineNotifier
{
    public function __construct(
        protected PanelNotificationDispatcher $dispatcher,
    ) {}

    /**
     * Alerte lorsqu'un participant entre en quarantaine atelier.
     *
     * @param RetreatParticipant $participant Participant concerné
     * @param string|null $reason Motif (ex. aucun atelier compatible)
     * @return void
     */
    public function notifySuperAdminsNewQuarantine(RetreatParticipant $participant, ?string $reason = null): void
    {
        $admins = $this->resolveSuperAdmins();

        if ($admins->isEmpty()) {
            return;
        }

        $message = sprintf(
            '%s %s (%s ans) attend une affectation atelier. %s',
            $participant->prenom,
            $participant->nom,
            $participant->age,
            filled($reason)
                ? 'Motif : '.$reason
                : 'Aucun atelier compatible n\'a été trouvé automatiquement.'
        );

        $this->dispatcher->notify(
            $admins,
            'Atelier en quarantaine',
            $message,
            ManageRetreatAtelierQuarantine::getUrl(panel: 'admin'),
            'warning',
            $participant,
        );
    }

    /**
     * Récapitulatif après une opération de réaffectation en masse.
     *
     * @param array{reassigned: int, quarantined: int, skipped: int} $stats Statistiques
     * @param string $context Libellé de l'opération (ex. nom atelier)
     * @return void
     */
    public function notifySuperAdminsReassignmentSummary(array $stats, string $context): void
    {
        $reassigned = (int) ($stats['reassigned'] ?? 0);
        $quarantined = (int) ($stats['quarantined'] ?? 0);
        $skipped = (int) ($stats['skipped'] ?? 0);

        if ($reassigned === 0 && $quarantined === 0) {
            return;
        }

        $admins = $this->resolveSuperAdmins();

        if ($admins->isEmpty()) {
            return;
        }

        $message = sprintf(
            '%s — %d réaffecté(s), %d en quarantaine, %d inchangé(s).',
            $context,
            $reassigned,
            $quarantined,
            $skipped,
        );

        $link = ManageRetreatAtelierQuarantine::getUrl(panel: 'admin');

        $this->dispatcher->notify(
            $admins,
            'Réaffectation ateliers terminée',
            $message,
            $link,
            $quarantined > 0 ? 'warning' : 'success',
        );
    }

    /**
     * @return Collection<int, User>
     */
    protected function resolveSuperAdmins(): Collection
    {
        $role = (string) config('filament-shield.super_admin.name', 'super_admin');

        return User::query()
            ->role($role)
            ->where('is_active', true)
            ->get();
    }

    /**
     * @param RetreatParticipant $participant Participant
     * @return string|null URL admin
     */
    protected function participantAdminUrl(RetreatParticipant $participant): ?string
    {
        try {
            return RetreatParticipantResource::getUrl('view', ['record' => $participant], panel: 'admin');
        } catch (\Throwable) {
            return null;
        }
    }
}
