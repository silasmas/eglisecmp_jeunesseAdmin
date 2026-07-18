<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SmsMessageLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Politique Shield : historique des envois SMS.
 */
class SmsMessageLogPolicy
{
    use HandlesAuthorization;

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SmsMessageLog');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param SmsMessageLog $smsMessageLog Enregistrement SMS
     * @return bool
     */
    public function view(AuthUser $authUser, SmsMessageLog $smsMessageLog): bool
    {
        return $authUser->can('View:SmsMessageLog');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SmsMessageLog');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param SmsMessageLog $smsMessageLog Enregistrement SMS
     * @return bool
     */
    public function update(AuthUser $authUser, SmsMessageLog $smsMessageLog): bool
    {
        return $authUser->can('Update:SmsMessageLog');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param SmsMessageLog $smsMessageLog Enregistrement SMS
     * @return bool
     */
    public function delete(AuthUser $authUser, SmsMessageLog $smsMessageLog): bool
    {
        return $authUser->can('Delete:SmsMessageLog');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SmsMessageLog');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param SmsMessageLog $smsMessageLog Enregistrement SMS
     * @return bool
     */
    public function restore(AuthUser $authUser, SmsMessageLog $smsMessageLog): bool
    {
        return $authUser->can('Restore:SmsMessageLog');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param SmsMessageLog $smsMessageLog Enregistrement SMS
     * @return bool
     */
    public function forceDelete(AuthUser $authUser, SmsMessageLog $smsMessageLog): bool
    {
        return $authUser->can('ForceDelete:SmsMessageLog');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SmsMessageLog');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SmsMessageLog');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param SmsMessageLog $smsMessageLog Enregistrement SMS
     * @return bool
     */
    public function replicate(AuthUser $authUser, SmsMessageLog $smsMessageLog): bool
    {
        return $authUser->can('Replicate:SmsMessageLog');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SmsMessageLog');
    }
}
