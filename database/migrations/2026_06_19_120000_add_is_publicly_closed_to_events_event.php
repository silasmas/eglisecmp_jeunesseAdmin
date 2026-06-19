<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permet de clôturer manuellement l'accès public à une retraite terminée.
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('events_event', function (Blueprint $table): void {
            $table->boolean('is_publicly_closed')
                ->default(false)
                ->after('is_active')
                ->comment('Acces public ferme (inscription, billet, QR, justificatif)');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('events_event', function (Blueprint $table): void {
            $table->dropColumn('is_publicly_closed');
        });
    }
};
