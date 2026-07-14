<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Résout les comptes super administrateur et adresses e-mail additionnelles pour notifications.
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
     * Adresses e-mail configurées en plus des comptes super_admin (ex. Jeunesse@eglisecmp.com).
     *
     * @return list<string>
     */
    public function additionalNotifyEmails(): array
    {
        return $this->normalizeEmails(config('retraite.admin_notify_emails', []));
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

    /**
     * E-mails super_admin + adresses additionnelles configurées.
     *
     * @return list<string>
     */
    public function resolveEmailAddresses(): array
    {
        return $this->resolveEmailAddressesForRoles([$this->roleName()]);
    }

    /**
     * E-mails des utilisateurs actifs pour les rôles donnés + adresses additionnelles configurées.
     *
     * @param array<int, string> $roles Rôles Spatie (ex. super_admin, panel_user)
     * @return list<string>
     */
    public function resolveEmailAddressesForRoles(array $roles): array
    {
        $userEmails = User::query()
            ->role($roles)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->pluck('email')
            ->all();

        return $this->normalizeEmails(array_merge($userEmails, $this->additionalNotifyEmails()));
    }

    /**
     * @param array<int, string> $emails Adresses brutes
     * @return list<string> Adresses uniques (casse préservée, dédoublonnage insensible à la casse)
     */
    public function normalizeEmails(array $emails): array
    {
        $seen = [];
        $normalized = [];

        foreach ($emails as $email) {
            $value = trim((string) $email);

            if ($value === '') {
                continue;
            }

            $key = strtolower($value);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $normalized[] = $value;
        }

        return $normalized;
    }
}
