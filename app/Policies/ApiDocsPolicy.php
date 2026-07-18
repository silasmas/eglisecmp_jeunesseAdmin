<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use ZPMLabs\FilamentApiDocsBuilder\Models\ApiDocs;

/**
 * Politique Shield : documentation API (plugin Api Docs).
 */
class ApiDocsPolicy
{
    use HandlesAuthorization;

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ApiDocs');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param ApiDocs $apiDocs Documentation API
     * @return bool
     */
    public function view(AuthUser $authUser, ApiDocs $apiDocs): bool
    {
        return $authUser->can('View:ApiDocs');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ApiDocs');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param ApiDocs $apiDocs Documentation API
     * @return bool
     */
    public function update(AuthUser $authUser, ApiDocs $apiDocs): bool
    {
        return $authUser->can('Update:ApiDocs');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param ApiDocs $apiDocs Documentation API
     * @return bool
     */
    public function delete(AuthUser $authUser, ApiDocs $apiDocs): bool
    {
        return $authUser->can('Delete:ApiDocs');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ApiDocs');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param ApiDocs $apiDocs Documentation API
     * @return bool
     */
    public function restore(AuthUser $authUser, ApiDocs $apiDocs): bool
    {
        return $authUser->can('Restore:ApiDocs');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param ApiDocs $apiDocs Documentation API
     * @return bool
     */
    public function forceDelete(AuthUser $authUser, ApiDocs $apiDocs): bool
    {
        return $authUser->can('ForceDelete:ApiDocs');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ApiDocs');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ApiDocs');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @param ApiDocs $apiDocs Documentation API
     * @return bool
     */
    public function replicate(AuthUser $authUser, ApiDocs $apiDocs): bool
    {
        return $authUser->can('Replicate:ApiDocs');
    }

    /**
     * @param AuthUser $authUser Utilisateur connecté
     * @return bool
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ApiDocs');
    }
}
