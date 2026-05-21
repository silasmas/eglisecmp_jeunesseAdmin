<?php

namespace App\Observers;

use App\Models\RetreatParticipant;
use App\Models\User;
use App\Services\PanelNotificationDispatcher;

class RetreatParticipantObserver
{
    public function __construct(
        protected PanelNotificationDispatcher $dispatcher,
    ) {}

    public function created(RetreatParticipant $participant): void
    {
        $users = $this->uniqueUsers($this->dispatcher->participantStakeholders($participant));
        $this->dispatcher->notify(
            $users,
            'Nouveau participant',
            sprintf('%s %s a été enregistré.', $participant->prenom, $participant->nom),
            null,
            'participant',
            $participant,
        );
    }

    public function updated(RetreatParticipant $participant): void
    {
        $users = $this->uniqueUsers($this->dispatcher->participantStakeholders($participant));

        if ($participant->wasChanged('paiement_valide')) {
            $this->dispatcher->notify(
                $users,
                'Paiement participant',
                sprintf(
                    'Paiement %s pour %s %s.',
                    $participant->paiement_valide ? 'validé' : 'non validé',
                    $participant->prenom,
                    $participant->nom
                ),
                null,
                $participant->paiement_valide ? 'success' : 'warning',
                $participant,
            );
        }

        if ($participant->wasChanged('registration_status')) {
            $this->dispatcher->notify(
                $users,
                'Inscription',
                sprintf('Statut d’inscription : %s (%s %s).', $participant->registration_status, $participant->prenom, $participant->nom),
                null,
                'info',
                $participant,
            );
        }

        if ($participant->wasChanged('present')) {
            $this->dispatcher->notify(
                $users,
                'Présence',
                sprintf(
                    'Présence %s pour %s %s.',
                    $participant->present ? 'confirmée' : 'non confirmée',
                    $participant->prenom,
                    $participant->nom
                ),
                null,
                'info',
                $participant,
            );
        }
    }

    /**
     * @param  array<int, User|null>  $users
     * @return list<User>
     */
    protected function uniqueUsers(array $users): array
    {
        $out = [];
        $seen = [];
        foreach ($users as $u) {
            if ($u && ! isset($seen[$u->id])) {
                $seen[$u->id] = true;
                $out[] = $u;
            }
        }

        return $out;
    }
}
