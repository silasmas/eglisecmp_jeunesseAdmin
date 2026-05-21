<?php

namespace Database\Seeders;

use Database\Seeders\Support\ShieldPermissionNames;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôles Shield et permissions Spatie (dump production).
 */
class ShieldRbacSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (ShieldPermissionNames::all() as $name) {
            Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::query()->firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        Role::query()->firstOrCreate([
            'name' => 'panel_user',
            'guard_name' => 'web',
        ]);

        Role::query()->firstOrCreate([
            'name' => 'ouvrier',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions(Permission::query()->pluck('id'));

        $this->command?->info('Shield : permissions et rôles (super_admin, panel_user, ouvrier) synchronisés.');
    }
}
