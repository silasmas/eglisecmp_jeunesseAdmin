<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChurchEvent;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ChurchEventPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ChurchEvent');
    }

    public function view(AuthUser $authUser, ChurchEvent $churchEvent): bool
    {
        return $authUser->can('View:ChurchEvent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ChurchEvent');
    }

    public function update(AuthUser $authUser, ChurchEvent $churchEvent): bool
    {
        return $authUser->can('Update:ChurchEvent');
    }

    public function delete(AuthUser $authUser, ChurchEvent $churchEvent): bool
    {
        return $authUser->can('Delete:ChurchEvent');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ChurchEvent');
    }

    public function restore(AuthUser $authUser, ChurchEvent $churchEvent): bool
    {
        return $authUser->can('Restore:ChurchEvent');
    }

    public function forceDelete(AuthUser $authUser, ChurchEvent $churchEvent): bool
    {
        return $authUser->can('ForceDelete:ChurchEvent');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ChurchEvent');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ChurchEvent');
    }

    public function replicate(AuthUser $authUser, ChurchEvent $churchEvent): bool
    {
        return $authUser->can('Replicate:ChurchEvent');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ChurchEvent');
    }
}
