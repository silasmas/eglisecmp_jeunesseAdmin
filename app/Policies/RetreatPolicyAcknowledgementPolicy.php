<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RetreatPolicyAcknowledgement;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RetreatPolicyAcknowledgementPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RetreatPolicyAcknowledgement');
    }

    public function view(AuthUser $authUser, RetreatPolicyAcknowledgement $retreatPolicyAcknowledgement): bool
    {
        return $authUser->can('View:RetreatPolicyAcknowledgement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RetreatPolicyAcknowledgement');
    }

    public function update(AuthUser $authUser, RetreatPolicyAcknowledgement $retreatPolicyAcknowledgement): bool
    {
        return $authUser->can('Update:RetreatPolicyAcknowledgement');
    }

    public function delete(AuthUser $authUser, RetreatPolicyAcknowledgement $retreatPolicyAcknowledgement): bool
    {
        return $authUser->can('Delete:RetreatPolicyAcknowledgement');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RetreatPolicyAcknowledgement');
    }

    public function restore(AuthUser $authUser, RetreatPolicyAcknowledgement $retreatPolicyAcknowledgement): bool
    {
        return $authUser->can('Restore:RetreatPolicyAcknowledgement');
    }

    public function forceDelete(AuthUser $authUser, RetreatPolicyAcknowledgement $retreatPolicyAcknowledgement): bool
    {
        return $authUser->can('ForceDelete:RetreatPolicyAcknowledgement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RetreatPolicyAcknowledgement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RetreatPolicyAcknowledgement');
    }

    public function replicate(AuthUser $authUser, RetreatPolicyAcknowledgement $retreatPolicyAcknowledgement): bool
    {
        return $authUser->can('Replicate:RetreatPolicyAcknowledgement');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RetreatPolicyAcknowledgement');
    }
}
