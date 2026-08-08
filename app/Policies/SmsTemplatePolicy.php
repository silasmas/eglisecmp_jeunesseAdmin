<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SmsTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Politique Shield : modèles SMS.
 */
class SmsTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * @param  AuthUser  $authUser  Utilisateur connecté
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SmsTemplate');
    }

    /**
     * @param  AuthUser  $authUser  Utilisateur connecté
     * @param  SmsTemplate  $smsTemplate  Modèle SMS
     */
    public function view(AuthUser $authUser, SmsTemplate $smsTemplate): bool
    {
        return $authUser->can('View:SmsTemplate');
    }

    /**
     * @param  AuthUser  $authUser  Utilisateur connecté
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SmsTemplate');
    }

    /**
     * @param  AuthUser  $authUser  Utilisateur connecté
     * @param  SmsTemplate  $smsTemplate  Modèle SMS
     */
    public function update(AuthUser $authUser, SmsTemplate $smsTemplate): bool
    {
        return $authUser->can('Update:SmsTemplate');
    }

    /**
     * @param  AuthUser  $authUser  Utilisateur connecté
     * @param  SmsTemplate  $smsTemplate  Modèle SMS
     */
    public function delete(AuthUser $authUser, SmsTemplate $smsTemplate): bool
    {
        return $authUser->can('Delete:SmsTemplate');
    }

    /**
     * @param  AuthUser  $authUser  Utilisateur connecté
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SmsTemplate');
    }

    /**
     * @param  AuthUser  $authUser  Utilisateur connecté
     * @param  SmsTemplate  $smsTemplate  Modèle SMS
     */
    public function restore(AuthUser $authUser, SmsTemplate $smsTemplate): bool
    {
        return $authUser->can('Restore:SmsTemplate');
    }

    /**
     * @param  AuthUser  $authUser  Utilisateur connecté
     * @param  SmsTemplate  $smsTemplate  Modèle SMS
     */
    public function forceDelete(AuthUser $authUser, SmsTemplate $smsTemplate): bool
    {
        return $authUser->can('ForceDelete:SmsTemplate');
    }

    /**
     * @param  AuthUser  $authUser  Utilisateur connecté
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SmsTemplate');
    }

    /**
     * @param  AuthUser  $authUser  Utilisateur connecté
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SmsTemplate');
    }

    /**
     * @param  AuthUser  $authUser  Utilisateur connecté
     * @param  SmsTemplate  $smsTemplate  Modèle SMS
     */
    public function replicate(AuthUser $authUser, SmsTemplate $smsTemplate): bool
    {
        return $authUser->can('Replicate:SmsTemplate');
    }

    /**
     * @param  AuthUser  $authUser  Utilisateur connecté
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SmsTemplate');
    }
}
