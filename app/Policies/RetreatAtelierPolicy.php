<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RetreatAtelier;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RetreatAtelierPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RetreatAtelier');
    }

    public function view(AuthUser $authUser, RetreatAtelier $retreatAtelier): bool
    {
        return $authUser->can('View:RetreatAtelier');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RetreatAtelier');
    }

    public function update(AuthUser $authUser, RetreatAtelier $retreatAtelier): bool
    {
        return $authUser->can('Update:RetreatAtelier');
    }

    public function delete(AuthUser $authUser, RetreatAtelier $retreatAtelier): bool
    {
        return $authUser->can('Delete:RetreatAtelier');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RetreatAtelier');
    }

    public function restore(AuthUser $authUser, RetreatAtelier $retreatAtelier): bool
    {
        return $authUser->can('Restore:RetreatAtelier');
    }

    public function forceDelete(AuthUser $authUser, RetreatAtelier $retreatAtelier): bool
    {
        return $authUser->can('ForceDelete:RetreatAtelier');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RetreatAtelier');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RetreatAtelier');
    }

    public function replicate(AuthUser $authUser, RetreatAtelier $retreatAtelier): bool
    {
        return $authUser->can('Replicate:RetreatAtelier');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RetreatAtelier');
    }
}
