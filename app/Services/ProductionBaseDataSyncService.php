<?php

namespace App\Services;

use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RetreatEssentialSeeder;
use Database\Seeders\ShieldRbacSeeder;
use Database\Seeders\SmsOperatorSeeder;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Synchronise les données de base (Shield, admin, SMS, retraite) sans données de démo.
 */
class ProductionBaseDataSyncService
{
    /**
     * @var list<class-string<Seeder>>
     */
    private const SEEDERS = [
        ShieldRbacSeeder::class,
        AdminUserSeeder::class,
        SmsOperatorSeeder::class,
        RetreatEssentialSeeder::class,
    ];

    /**
     * Exécute les seeders de base production dans l'ordre défini.
     *
     * @return void
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::SEEDERS as $seederClass) {
            /** @var Seeder $seeder */
            $seeder = app($seederClass);
            $seeder->run();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
