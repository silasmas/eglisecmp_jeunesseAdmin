<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RetreatRetreatDetail;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RetreatRetreatDetailPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RetreatRetreatDetail');
    }

    public function view(AuthUser $authUser, RetreatRetreatDetail $retreatRetreatDetail): bool
    {
        return $authUser->can('View:RetreatRetreatDetail');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RetreatRetreatDetail');
    }

    public function update(AuthUser $authUser, RetreatRetreatDetail $retreatRetreatDetail): bool
    {
        return $authUser->can('Update:RetreatRetreatDetail');
    }

    public function delete(AuthUser $authUser, RetreatRetreatDetail $retreatRetreatDetail): bool
    {
        return $authUser->can('Delete:RetreatRetreatDetail');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RetreatRetreatDetail');
    }

    public function restore(AuthUser $authUser, RetreatRetreatDetail $retreatRetreatDetail): bool
    {
        return $authUser->can('Restore:RetreatRetreatDetail');
    }

    public function forceDelete(AuthUser $authUser, RetreatRetreatDetail $retreatRetreatDetail): bool
    {
        return $authUser->can('ForceDelete:RetreatRetreatDetail');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RetreatRetreatDetail');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RetreatRetreatDetail');
    }

    public function replicate(AuthUser $authUser, RetreatRetreatDetail $retreatRetreatDetail): bool
    {
        return $authUser->can('Replicate:RetreatRetreatDetail');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RetreatRetreatDetail');
    }
}
