<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RegistrationFormConfigSet;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Politique d'accès à la configuration du formulaire d'inscription.
 */
class RegistrationFormConfigSetPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RegistrationFormConfigSet');
    }

    public function view(AuthUser $authUser, RegistrationFormConfigSet $registrationFormConfigSet): bool
    {
        return $authUser->can('View:RegistrationFormConfigSet');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RegistrationFormConfigSet');
    }

    public function update(AuthUser $authUser, RegistrationFormConfigSet $registrationFormConfigSet): bool
    {
        return $authUser->can('Update:RegistrationFormConfigSet');
    }

    public function delete(AuthUser $authUser, RegistrationFormConfigSet $registrationFormConfigSet): bool
    {
        return $authUser->can('Delete:RegistrationFormConfigSet');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RegistrationFormConfigSet');
    }

    public function restore(AuthUser $authUser, RegistrationFormConfigSet $registrationFormConfigSet): bool
    {
        return $authUser->can('Restore:RegistrationFormConfigSet');
    }

    public function forceDelete(AuthUser $authUser, RegistrationFormConfigSet $registrationFormConfigSet): bool
    {
        return $authUser->can('ForceDelete:RegistrationFormConfigSet');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RegistrationFormConfigSet');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RegistrationFormConfigSet');
    }

    public function replicate(AuthUser $authUser, RegistrationFormConfigSet $registrationFormConfigSet): bool
    {
        return $authUser->can('Replicate:RegistrationFormConfigSet');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RegistrationFormConfigSet');
    }

    /**
     * Déverrouiller les champs critiques du formulaire d'inscription.
     */
    public function unlock(AuthUser $authUser, RegistrationFormConfigSet $registrationFormConfigSet): bool
    {
        if ($authUser instanceof User && $authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('Unlock:RegistrationFormConfigSet');
    }
}
