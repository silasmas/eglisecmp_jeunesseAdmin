<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RetreatParticipantDeletionLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RetreatParticipantDeletionLogPolicy
{
    use HandlesAuthorization;

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RetreatParticipant');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param RetreatParticipantDeletionLog $retreatParticipantDeletionLog Entrée d'historique
     * @return bool
     */
    public function view(AuthUser $authUser, RetreatParticipantDeletionLog $retreatParticipantDeletionLog): bool
    {
        return $authUser->can('DeleteAny:RetreatParticipant');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param RetreatParticipantDeletionLog $retreatParticipantDeletionLog Entrée d'historique
     * @return bool
     */
    public function update(AuthUser $authUser, RetreatParticipantDeletionLog $retreatParticipantDeletionLog): bool
    {
        return false;
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param RetreatParticipantDeletionLog $retreatParticipantDeletionLog Entrée d'historique
     * @return bool
     */
    public function delete(AuthUser $authUser, RetreatParticipantDeletionLog $retreatParticipantDeletionLog): bool
    {
        return false;
    }
}
