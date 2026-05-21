<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RetreatParticipant;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RetreatParticipantPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RetreatParticipant');
    }

    public function view(AuthUser $authUser, RetreatParticipant $retreatParticipant): bool
    {
        return $authUser->can('View:RetreatParticipant');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RetreatParticipant');
    }

    public function update(AuthUser $authUser, RetreatParticipant $retreatParticipant): bool
    {
        return $authUser->can('Update:RetreatParticipant');
    }

    public function delete(AuthUser $authUser, RetreatParticipant $retreatParticipant): bool
    {
        return $authUser->can('Delete:RetreatParticipant');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RetreatParticipant');
    }

    public function restore(AuthUser $authUser, RetreatParticipant $retreatParticipant): bool
    {
        return $authUser->can('Restore:RetreatParticipant');
    }

    public function forceDelete(AuthUser $authUser, RetreatParticipant $retreatParticipant): bool
    {
        return $authUser->can('ForceDelete:RetreatParticipant');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RetreatParticipant');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RetreatParticipant');
    }

    public function replicate(AuthUser $authUser, RetreatParticipant $retreatParticipant): bool
    {
        return $authUser->can('Replicate:RetreatParticipant');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RetreatParticipant');
    }
}
