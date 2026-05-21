<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RetreatNotification;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RetreatNotificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RetreatNotification');
    }

    public function view(AuthUser $authUser, RetreatNotification $retreatNotification): bool
    {
        return $authUser->can('View:RetreatNotification');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RetreatNotification');
    }

    public function update(AuthUser $authUser, RetreatNotification $retreatNotification): bool
    {
        return $authUser->can('Update:RetreatNotification');
    }

    public function delete(AuthUser $authUser, RetreatNotification $retreatNotification): bool
    {
        return $authUser->can('Delete:RetreatNotification');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RetreatNotification');
    }

    public function restore(AuthUser $authUser, RetreatNotification $retreatNotification): bool
    {
        return $authUser->can('Restore:RetreatNotification');
    }

    public function forceDelete(AuthUser $authUser, RetreatNotification $retreatNotification): bool
    {
        return $authUser->can('ForceDelete:RetreatNotification');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RetreatNotification');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RetreatNotification');
    }

    public function replicate(AuthUser $authUser, RetreatNotification $retreatNotification): bool
    {
        return $authUser->can('Replicate:RetreatNotification');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RetreatNotification');
    }
}
