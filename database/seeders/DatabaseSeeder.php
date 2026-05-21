<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seed par défaut : données de base (production / développement backend).
 *
 * Pour les données de démo volumineuses (participants, notifications, etc.) :
 * php artisan db:seed --class=DemoDatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $this->call(ProductionBaseSeeder::class);
    }
}
