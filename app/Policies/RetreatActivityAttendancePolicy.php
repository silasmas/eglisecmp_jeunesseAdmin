<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RetreatActivityAttendance;
use App\Services\RetreatAtelierAuthorizationService;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RetreatActivityAttendancePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RetreatActivityAttendance');
    }

    public function view(AuthUser $authUser, RetreatActivityAttendance $retreatActivityAttendance): bool
    {
        return $authUser->can('View:RetreatActivityAttendance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RetreatActivityAttendance');
    }

    public function update(AuthUser $authUser, RetreatActivityAttendance $retreatActivityAttendance): bool
    {
        if (! $authUser->can('Update:RetreatActivityAttendance')) {
            return false;
        }

        $retreatActivityAttendance->loadMissing('participant.atelier');

        return app(RetreatAtelierAuthorizationService::class)
            ->canManageParticipant($authUser, $retreatActivityAttendance->participant);
    }

    public function delete(AuthUser $authUser, RetreatActivityAttendance $retreatActivityAttendance): bool
    {
        if (! $authUser->can('Delete:RetreatActivityAttendance')) {
            return false;
        }

        $retreatActivityAttendance->loadMissing('participant.atelier');

        return app(RetreatAtelierAuthorizationService::class)
            ->canManageParticipant($authUser, $retreatActivityAttendance->participant);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RetreatActivityAttendance');
    }

    public function restore(AuthUser $authUser, RetreatActivityAttendance $retreatActivityAttendance): bool
    {
        return $authUser->can('Restore:RetreatActivityAttendance');
    }

    public function forceDelete(AuthUser $authUser, RetreatActivityAttendance $retreatActivityAttendance): bool
    {
        return $authUser->can('ForceDelete:RetreatActivityAttendance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RetreatActivityAttendance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RetreatActivityAttendance');
    }

    public function replicate(AuthUser $authUser, RetreatActivityAttendance $retreatActivityAttendance): bool
    {
        return $authUser->can('Replicate:RetreatActivityAttendance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RetreatActivityAttendance');
    }
}
