<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RetreatPayment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RetreatPaymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RetreatPayment');
    }

    public function view(AuthUser $authUser, RetreatPayment $retreatPayment): bool
    {
        return $authUser->can('View:RetreatPayment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RetreatPayment');
    }

    public function update(AuthUser $authUser, RetreatPayment $retreatPayment): bool
    {
        return $authUser->can('Update:RetreatPayment');
    }

    public function delete(AuthUser $authUser, RetreatPayment $retreatPayment): bool
    {
        return $authUser->can('Delete:RetreatPayment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RetreatPayment');
    }

    public function restore(AuthUser $authUser, RetreatPayment $retreatPayment): bool
    {
        return $authUser->can('Restore:RetreatPayment');
    }

    public function forceDelete(AuthUser $authUser, RetreatPayment $retreatPayment): bool
    {
        return $authUser->can('ForceDelete:RetreatPayment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RetreatPayment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RetreatPayment');
    }

    public function replicate(AuthUser $authUser, RetreatPayment $retreatPayment): bool
    {
        return $authUser->can('Replicate:RetreatPayment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RetreatPayment');
    }
}
