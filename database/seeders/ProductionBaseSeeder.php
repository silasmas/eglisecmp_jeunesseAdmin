<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Données de base pour faire fonctionner le backend (Shield, admin, SMS, retraite).
 * Aligné sur le dump HeidiSQL `cmp_jeunesse` — sans données de démo volumineuses.
 */
class ProductionBaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ShieldRbacSeeder::class,
            AdminUserSeeder::class,
            SmsOperatorSeeder::class,
            SmsTemplateSeeder::class,
            RetreatEssentialSeeder::class,
        ]);

        $this->command?->info('Seed de base production terminé.');
    }
}
