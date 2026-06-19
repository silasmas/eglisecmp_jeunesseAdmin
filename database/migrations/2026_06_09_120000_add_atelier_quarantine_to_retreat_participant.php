<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quarantaine atelier : inscription autorisée, affectation reportée jusqu'à réaffectation admin.
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('retreat_participant', function (Blueprint $table): void {
            $table->boolean('atelier_quarantine')->default(false)->after('atelier_id');
            $table->timestamp('atelier_quarantine_at')->nullable()->after('atelier_quarantine');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('retreat_participant', function (Blueprint $table): void {
            $table->dropColumn(['atelier_quarantine', 'atelier_quarantine_at']);
        });
    }
};
