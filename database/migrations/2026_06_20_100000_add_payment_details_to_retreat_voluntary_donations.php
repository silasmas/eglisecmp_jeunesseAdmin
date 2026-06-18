<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Détails de paiement (opérateur, numéro Mobile Money) pour l'historique admin.
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('retreat_voluntary_donations', function (Blueprint $table): void {
            $table->string('payment_operator', 64)->nullable()->after('payment_channel');
            $table->string('payment_phone', 30)->nullable()->after('payment_operator');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('retreat_voluntary_donations', function (Blueprint $table): void {
            $table->dropColumn(['payment_operator', 'payment_phone']);
        });
    }
};
