<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fenêtre d'ouverture du formulaire d'inscription publique (distincte du planning de l'événement).
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('events_event', function (Blueprint $table): void {
            $table->dateTime('public_registration_opens_at')
                ->nullable()
                ->after('is_publicly_closed')
                ->comment('Début des inscriptions en ligne (vide = ouvert dès que les autres conditions sont remplies)');

            $table->dateTime('public_registration_closes_at')
                ->nullable()
                ->after('public_registration_opens_at')
                ->comment('Fin des inscriptions en ligne (vide = repli sur la date de fin de l\'événement)');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('events_event', function (Blueprint $table): void {
            $table->dropColumn([
                'public_registration_opens_at',
                'public_registration_closes_at',
            ]);
        });
    }
};
