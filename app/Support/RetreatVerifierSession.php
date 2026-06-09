<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Session ouvrier/staff du portail de vérification QR retraite.
 */
class RetreatVerifierSession
{
    private const SESSION_USER_ID = 'retreat_verifier_user_id';

    /**
     * Retourne l'utilisateur staff connecté au portail de vérification, ou null.
     */
    public static function currentUser(): ?User
    {
        $id = session(self::SESSION_USER_ID);

        if (! $id) {
            return null;
        }

        $user = User::query()->find($id);

        if (! $user || ! self::canVerifyRetreatRegistrations($user)) {
            return null;
        }

        return $user;
    }

    /**
     * Indique si l'utilisateur peut vérifier des inscriptions via le portail QR.
     */
    public static function canVerifyRetreatRegistrations(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'panel_user', 'ouvrier', 'worker', 'staff'])) {
            return true;
        }

        $function = Str::lower((string) $user->fonction_metier);

        return in_array($function, [
            'ouvrier',
            'worker',
            'staff',
            'encadreur',
            'responsable_chambre',
            'responsable_atelier',
        ], true);
    }
}
