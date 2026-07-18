<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SmsOperator;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Politique Shield : opérateurs SMS.
 */
class SmsOperatorPolicy
{
    use HandlesAuthorization;

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SmsOperator');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param SmsOperator $smsOperator Opérateur SMS
     * @return bool
     */
    public function view(AuthUser $authUser, SmsOperator $smsOperator): bool
    {
        return $authUser->can('View:SmsOperator');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SmsOperator');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param SmsOperator $smsOperator Opérateur SMS
     * @return bool
     */
    public function update(AuthUser $authUser, SmsOperator $smsOperator): bool
    {
        return $authUser->can('Update:SmsOperator');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param SmsOperator $smsOperator Opérateur SMS
     * @return bool
     */
    public function delete(AuthUser $authUser, SmsOperator $smsOperator): bool
    {
        return $authUser->can('Delete:SmsOperator');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SmsOperator');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param SmsOperator $smsOperator Opérateur SMS
     * @return bool
     */
    public function restore(AuthUser $authUser, SmsOperator $smsOperator): bool
    {
        return $authUser->can('Restore:SmsOperator');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param SmsOperator $smsOperator Opérateur SMS
     * @return bool
     */
    public function forceDelete(AuthUser $authUser, SmsOperator $smsOperator): bool
    {
        return $authUser->can('ForceDelete:SmsOperator');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SmsOperator');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SmsOperator');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param SmsOperator $smsOperator Opérateur SMS
     * @return bool
     */
    public function replicate(AuthUser $authUser, SmsOperator $smsOperator): bool
    {
        return $authUser->can('Replicate:SmsOperator');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SmsOperator');
    }
}
