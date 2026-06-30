<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la date d'archivage pour exclure les retraites clôturées des vues opérationnelles.
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('events_event', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->after('is_publicly_closed');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('events_event', function (Blueprint $table): void {
            $table->dropColumn('archived_at');
        });
    }
};
