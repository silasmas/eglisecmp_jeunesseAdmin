<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table d'historique des suppressions de participants retraite.
     */
    public function up(): void
    {
        Schema::create('retreat_participant_deletion_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('performed_by')->constrained('users')->comment('Administrateur ayant supprimé');
            $table->foreignId('event_id')->nullable()->constrained('events_event')->nullOnDelete()->comment('Événement si unique dans la sélection');
            $table->unsignedSmallInteger('participant_count')->default(0)->comment('Nombre de participants supprimés');
            $table->text('participants_summary')->comment('Snapshot compact des participants (séparateur / et ||)');
            $table->text('related_summary')->comment('Données liées supprimées (format compact)');
            $table->timestamps();

            $table->index(['performed_by', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Supprime la table d'historique des suppressions.
     */
    public function down(): void
    {
        Schema::dropIfExists('retreat_participant_deletion_logs');
    }
};
