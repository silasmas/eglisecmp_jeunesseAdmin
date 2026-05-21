<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Compte administrateur principal (équivalent user id=1 du dump).
 */
class AdminUserSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $email = (string) env('SEED_ADMIN_EMAIL', 'admin@example.com');
        $name = (string) env('SEED_ADMIN_NAME', 'Administrateur');

        $password = env('SEED_ADMIN_PASSWORD_HASH');
        if (! is_string($password) || $password === '') {
            $plain = (string) env('SEED_ADMIN_PASSWORD', 'password');
            $password = Hash::make($plain);
        }

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $superAdminRole = config('filament-shield.super_admin.name', 'super_admin');
        $admin->syncRoles([$superAdminRole]);

        $panelEmail = env('SEED_PANEL_USER_EMAIL');
        if (is_string($panelEmail) && $panelEmail !== '') {
            $panelUser = User::query()->updateOrCreate(
                ['email' => $panelEmail],
                [
                    'name' => (string) env('SEED_PANEL_USER_NAME', 'Utilisateur panel'),
                    'password' => $password,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $panelRole = config('filament-shield.panel_user.name', 'panel_user');
            $panelUser->syncRoles([$panelRole]);
        }

        $this->command?->info("Utilisateur admin : {$email}");
    }
}
