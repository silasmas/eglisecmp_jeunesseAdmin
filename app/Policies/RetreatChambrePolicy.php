<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RetreatChambre;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RetreatChambrePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RetreatChambre');
    }

    public function view(AuthUser $authUser, RetreatChambre $retreatChambre): bool
    {
        return $authUser->can('View:RetreatChambre');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RetreatChambre');
    }

    public function update(AuthUser $authUser, RetreatChambre $retreatChambre): bool
    {
        return $authUser->can('Update:RetreatChambre');
    }

    public function delete(AuthUser $authUser, RetreatChambre $retreatChambre): bool
    {
        return $authUser->can('Delete:RetreatChambre');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RetreatChambre');
    }

    public function restore(AuthUser $authUser, RetreatChambre $retreatChambre): bool
    {
        return $authUser->can('Restore:RetreatChambre');
    }

    public function forceDelete(AuthUser $authUser, RetreatChambre $retreatChambre): bool
    {
        return $authUser->can('ForceDelete:RetreatChambre');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RetreatChambre');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RetreatChambre');
    }

    public function replicate(AuthUser $authUser, RetreatChambre $retreatChambre): bool
    {
        return $authUser->can('Replicate:RetreatChambre');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RetreatChambre');
    }
}
