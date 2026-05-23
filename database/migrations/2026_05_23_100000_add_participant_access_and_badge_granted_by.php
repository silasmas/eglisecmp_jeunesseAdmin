<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Traçabilité : ouvrier/admin qui accorde l'accès retraite et remet le badge.
     */
    public function up(): void
    {
        Schema::table('retreat_participant', function (Blueprint $table): void {
            $table->foreignId('retreat_access_granted_by')
                ->nullable()
                ->after('date_presence')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Utilisateur ayant donne acces a la retraite');
            $table->foreignId('badge_received_by')
                ->nullable()
                ->after('badge_received_at')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Utilisateur ayant remis le badge physique');
        });
    }

    public function down(): void
    {
        Schema::table('retreat_participant', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('retreat_access_granted_by');
            $table->dropConstrainedForeignId('badge_received_by');
        });
    }
};
