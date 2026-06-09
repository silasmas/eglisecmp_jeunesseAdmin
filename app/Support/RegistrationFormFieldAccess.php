<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Contrôle d'accès aux actions sensibles sur la configuration du formulaire.
 */
class RegistrationFormFieldAccess
{
    /**
     * Indique si l'utilisateur peut déverrouiller les champs critiques du registre.
     */
    public static function canUnlockLockedFields(Authenticatable|null $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->can('Unlock:RegistrationFormConfigSet');
    }
}
