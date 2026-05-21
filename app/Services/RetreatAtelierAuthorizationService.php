<?php

namespace App\Services;

use App\Models\RetreatAtelier;
use App\Models\RetreatParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Vérifie si un utilisateur peut agir pour un atelier (responsable, adjoint ou admin).
 */
class RetreatAtelierAuthorizationService
{
    /**
     * Indique si l'utilisateur est super administrateur (accès global aux ateliers).
     */
    public function isSuperAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin');
    }

    /**
     * Indique si l'utilisateur est administrateur panel (super_admin ou panel_user).
     */
    public function isPanelAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'panel_user']);
    }

    /**
     * Indique si l'utilisateur est responsable ou adjoint d'au moins un atelier actif.
     */
    public function isAtelierLead(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return RetreatAtelier::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($user): void {
                $query->where('responsable_user_id', $user->id)
                    ->orWhere('adjoint_user_id', $user->id);
            })
            ->exists();
    }

    /**
     * Indique si l'utilisateur gère au moins un atelier actif (responsable, adjoint ou super_admin).
     */
    public function managesAnyAtelier(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->isAtelierLead($user);
    }

    /**
     * Indique si l'utilisateur peut gérer l'atelier (pointage, mouvements, rapport).
     */
    public function canManageAtelier(?User $user, ?RetreatAtelier $atelier): bool
    {
        if (! $user || ! $atelier) {
            return false;
        }

        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return (int) $atelier->responsable_user_id === (int) $user->id
            || (int) $atelier->adjoint_user_id === (int) $user->id;
    }

    /**
     * Indique si l'utilisateur peut agir sur un participant via son atelier.
     */
    public function canManageParticipant(?User $user, ?RetreatParticipant $participant): bool
    {
        if (! $user || ! $participant) {
            return false;
        }

        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->canManageAtelier($user, $participant->atelier);
    }

    /**
     * Retourne les identifiants d'ateliers gérés par l'utilisateur.
     *
     * @return Collection<int, int>
     */
    public function managedAtelierIds(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        if ($this->isSuperAdmin($user)) {
            return RetreatAtelier::query()
                ->where('is_active', true)
                ->pluck('id');
        }

        return RetreatAtelier::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($user): void {
                $query->where('responsable_user_id', $user->id)
                    ->orWhere('adjoint_user_id', $user->id);
            })
            ->pluck('id');
    }

    /**
     * Restreint une requête participants aux ateliers gérés (sauf super_admin).
     *
     * @param  Builder<\App\Models\RetreatParticipant>  $query
     */
    public function scopeParticipantsForUser(Builder $query, ?User $user): Builder
    {
        if (! $user || $this->isSuperAdmin($user)) {
            return $query;
        }

        $atelierIds = $this->managedAtelierIds($user);

        if ($atelierIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('atelier_id', $atelierIds);
    }

    /**
     * Restreint une requête pointages aux participants des ateliers gérés.
     *
     * @param  Builder<\App\Models\RetreatActivityAttendance>  $query
     */
    public function scopeAttendancesForUser(Builder $query, ?User $user): Builder
    {
        if (! $user || $this->isSuperAdmin($user)) {
            return $query;
        }

        $atelierIds = $this->managedAtelierIds($user);

        if ($atelierIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('participant', fn (Builder $participantQuery) => $participantQuery->whereIn('atelier_id', $atelierIds));
    }

    /**
     * Restreint une requête mouvements aux participants des ateliers gérés.
     *
     * @param  Builder<\App\Models\RetreatParticipantMovement>  $query
     */
    public function scopeMovementsForUser(Builder $query, ?User $user): Builder
    {
        if (! $user || $this->isSuperAdmin($user)) {
            return $query;
        }

        $atelierIds = $this->managedAtelierIds($user);

        if ($atelierIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('participant', fn (Builder $participantQuery) => $participantQuery->whereIn('atelier_id', $atelierIds));
    }
}
