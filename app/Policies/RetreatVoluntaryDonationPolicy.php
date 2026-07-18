<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RetreatVoluntaryDonation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Politique Shield : dons volontaires retraite.
 */
class RetreatVoluntaryDonationPolicy
{
    use HandlesAuthorization;

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RetreatVoluntaryDonation');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param RetreatVoluntaryDonation $retreatVoluntaryDonation Don
     * @return bool
     */
    public function view(AuthUser $authUser, RetreatVoluntaryDonation $retreatVoluntaryDonation): bool
    {
        return $authUser->can('View:RetreatVoluntaryDonation');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RetreatVoluntaryDonation');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param RetreatVoluntaryDonation $retreatVoluntaryDonation Don
     * @return bool
     */
    public function update(AuthUser $authUser, RetreatVoluntaryDonation $retreatVoluntaryDonation): bool
    {
        return $authUser->can('Update:RetreatVoluntaryDonation');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param RetreatVoluntaryDonation $retreatVoluntaryDonation Don
     * @return bool
     */
    public function delete(AuthUser $authUser, RetreatVoluntaryDonation $retreatVoluntaryDonation): bool
    {
        return $authUser->can('Delete:RetreatVoluntaryDonation');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RetreatVoluntaryDonation');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param RetreatVoluntaryDonation $retreatVoluntaryDonation Don
     * @return bool
     */
    public function restore(AuthUser $authUser, RetreatVoluntaryDonation $retreatVoluntaryDonation): bool
    {
        return $authUser->can('Restore:RetreatVoluntaryDonation');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param RetreatVoluntaryDonation $retreatVoluntaryDonation Don
     * @return bool
     */
    public function forceDelete(AuthUser $authUser, RetreatVoluntaryDonation $retreatVoluntaryDonation): bool
    {
        return $authUser->can('ForceDelete:RetreatVoluntaryDonation');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RetreatVoluntaryDonation');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RetreatVoluntaryDonation');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param RetreatVoluntaryDonation $retreatVoluntaryDonation Don
     * @return bool
     */
    public function replicate(AuthUser $authUser, RetreatVoluntaryDonation $retreatVoluntaryDonation): bool
    {
        return $authUser->can('Replicate:RetreatVoluntaryDonation');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RetreatVoluntaryDonation');
    }
}
