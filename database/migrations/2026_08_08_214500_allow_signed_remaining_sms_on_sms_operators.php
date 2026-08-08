<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keccel peut renvoyer un solde négatif (compte expiré / crédits hors borne) :
 * la colonne UNSIGNED provoquait SQLSTATE[22003].
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sms_operators')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE sms_operators MODIFY remaining_sms INT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('sms_operators')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE sms_operators MODIFY remaining_sms INT UNSIGNED NULL');
        }
    }
};
