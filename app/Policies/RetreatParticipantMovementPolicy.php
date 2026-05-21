<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RetreatParticipantMovement;
use App\Services\RetreatAtelierAuthorizationService;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RetreatParticipantMovementPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RetreatParticipantMovement');
    }

    public function view(AuthUser $authUser, RetreatParticipantMovement $retreatParticipantMovement): bool
    {
        return $authUser->can('View:RetreatParticipantMovement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RetreatParticipantMovement');
    }

    public function update(AuthUser $authUser, RetreatParticipantMovement $retreatParticipantMovement): bool
    {
        if (! $authUser->can('Update:RetreatParticipantMovement')) {
            return false;
        }

        $retreatParticipantMovement->loadMissing('participant.atelier');

        return app(RetreatAtelierAuthorizationService::class)
            ->canManageParticipant($authUser, $retreatParticipantMovement->participant);
    }

    public function delete(AuthUser $authUser, RetreatParticipantMovement $retreatParticipantMovement): bool
    {
        if (! $authUser->can('Delete:RetreatParticipantMovement')) {
            return false;
        }

        $retreatParticipantMovement->loadMissing('participant.atelier');

        return app(RetreatAtelierAuthorizationService::class)
            ->canManageParticipant($authUser, $retreatParticipantMovement->participant);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RetreatParticipantMovement');
    }

    public function restore(AuthUser $authUser, RetreatParticipantMovement $retreatParticipantMovement): bool
    {
        return $authUser->can('Restore:RetreatParticipantMovement');
    }

    public function forceDelete(AuthUser $authUser, RetreatParticipantMovement $retreatParticipantMovement): bool
    {
        return $authUser->can('ForceDelete:RetreatParticipantMovement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RetreatParticipantMovement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RetreatParticipantMovement');
    }

    public function replicate(AuthUser $authUser, RetreatParticipantMovement $retreatParticipantMovement): bool
    {
        return $authUser->can('Replicate:RetreatParticipantMovement');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RetreatParticipantMovement');
    }
}
