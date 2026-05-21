<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verrouillage du compte-rendu après soumission définitive.
     */
    public function up(): void
    {
        Schema::table('retreat_activity_atelier_reports', function (Blueprint $table): void {
            $table->timestamp('submitted_at')->nullable()->after('recorded_by');
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::table('retreat_activity_atelier_reports', function (Blueprint $table): void {
            $table->dropColumn('submitted_at');
        });
    }
};
