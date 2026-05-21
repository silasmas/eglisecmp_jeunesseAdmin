<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RetreatActivityPlan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RetreatActivityPlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RetreatActivityPlan');
    }

    public function view(AuthUser $authUser, RetreatActivityPlan $retreatActivityPlan): bool
    {
        return $authUser->can('View:RetreatActivityPlan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RetreatActivityPlan');
    }

    public function update(AuthUser $authUser, RetreatActivityPlan $retreatActivityPlan): bool
    {
        return $authUser->can('Update:RetreatActivityPlan');
    }

    public function delete(AuthUser $authUser, RetreatActivityPlan $retreatActivityPlan): bool
    {
        return $authUser->can('Delete:RetreatActivityPlan');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RetreatActivityPlan');
    }

    public function restore(AuthUser $authUser, RetreatActivityPlan $retreatActivityPlan): bool
    {
        return $authUser->can('Restore:RetreatActivityPlan');
    }

    public function forceDelete(AuthUser $authUser, RetreatActivityPlan $retreatActivityPlan): bool
    {
        return $authUser->can('ForceDelete:RetreatActivityPlan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RetreatActivityPlan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RetreatActivityPlan');
    }

    public function replicate(AuthUser $authUser, RetreatActivityPlan $retreatActivityPlan): bool
    {
        return $authUser->can('Replicate:RetreatActivityPlan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RetreatActivityPlan');
    }
}
