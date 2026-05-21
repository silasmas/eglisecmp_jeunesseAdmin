<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RetreatSession;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RetreatSessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RetreatSession');
    }

    public function view(AuthUser $authUser, RetreatSession $retreatSession): bool
    {
        return $authUser->can('View:RetreatSession');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RetreatSession');
    }

    public function update(AuthUser $authUser, RetreatSession $retreatSession): bool
    {
        return $authUser->can('Update:RetreatSession');
    }

    public function delete(AuthUser $authUser, RetreatSession $retreatSession): bool
    {
        return $authUser->can('Delete:RetreatSession');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RetreatSession');
    }

    public function restore(AuthUser $authUser, RetreatSession $retreatSession): bool
    {
        return $authUser->can('Restore:RetreatSession');
    }

    public function forceDelete(AuthUser $authUser, RetreatSession $retreatSession): bool
    {
        return $authUser->can('ForceDelete:RetreatSession');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RetreatSession');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RetreatSession');
    }

    public function replicate(AuthUser $authUser, RetreatSession $retreatSession): bool
    {
        return $authUser->can('Replicate:RetreatSession');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RetreatSession');
    }
}
