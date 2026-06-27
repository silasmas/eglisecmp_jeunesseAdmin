<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Résout les comptes super administrateur pour notifications et e-mails.
 */
class SuperAdminRecipientResolver
{
    /**
     * @return string Nom du rôle Shield super admin
     */
    public function roleName(): string
    {
        return (string) config('filament-shield.super_admin.name', 'super_admin');
    }

    /**
     * Tous les super_admin possédant une adresse e-mail (sans filtre is_active).
     *
     * @return Collection<int, User>
     */
    public function recipientsForEmail(): Collection
    {
        return User::query()
            ->role($this->roleName())
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get()
            ->unique(fn (User $user): string => strtolower(trim((string) $user->email)))
            ->values();
    }

    /**
     * Super_admin actifs pour notifications Filament.
     *
     * @return Collection<int, User>
     */
    public function recipientsForPanelNotifications(): Collection
    {
        return User::query()
            ->role($this->roleName())
            ->where('is_active', true)
            ->get();
    }
}
