<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RetreatPolicy;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RetreatPolicyPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RetreatPolicy');
    }

    public function view(AuthUser $authUser, RetreatPolicy $retreatPolicy): bool
    {
        return $authUser->can('View:RetreatPolicy');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RetreatPolicy');
    }

    public function update(AuthUser $authUser, RetreatPolicy $retreatPolicy): bool
    {
        return $authUser->can('Update:RetreatPolicy');
    }

    public function delete(AuthUser $authUser, RetreatPolicy $retreatPolicy): bool
    {
        return $authUser->can('Delete:RetreatPolicy');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RetreatPolicy');
    }

    public function restore(AuthUser $authUser, RetreatPolicy $retreatPolicy): bool
    {
        return $authUser->can('Restore:RetreatPolicy');
    }

    public function forceDelete(AuthUser $authUser, RetreatPolicy $retreatPolicy): bool
    {
        return $authUser->can('ForceDelete:RetreatPolicy');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RetreatPolicy');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RetreatPolicy');
    }

    public function replicate(AuthUser $authUser, RetreatPolicy $retreatPolicy): bool
    {
        return $authUser->can('Replicate:RetreatPolicy');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RetreatPolicy');
    }
}
