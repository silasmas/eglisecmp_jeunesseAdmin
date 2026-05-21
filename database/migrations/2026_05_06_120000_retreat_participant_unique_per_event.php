<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remplace l’index unique global (nom, prenom) par un index par événement,
 * pour permettre deux retraites distinctes avec le même nom/prénom.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['retreat_participant_nom_prenom_unique'] as $indexName) {
            try {
                Schema::table('retreat_participant', function (Blueprint $table) use ($indexName) {
                    $table->dropUnique($indexName);
                });
            } catch (Throwable) {
                // Index déjà absent ou nom différent selon l’historique des migrations.
            }
        }

        $conn = Schema::getConnection();
        $db = $conn->getDatabaseName();
        $already = collect($conn->select(
            'SELECT 1 AS o FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$db, 'retreat_participant', 'retreat_participant_event_nom_prenom_postnom_unique']
        ))->isNotEmpty();

        if (! $already) {
            Schema::table('retreat_participant', function (Blueprint $table) {
                $table->unique(
                    ['event_id', 'nom', 'prenom', 'postnom'],
                    'retreat_participant_event_nom_prenom_postnom_unique'
                );
            });
        }
    }

    public function down(): void
    {
        try {
            Schema::table('retreat_participant', function (Blueprint $table) {
                $table->dropUnique('retreat_participant_event_nom_prenom_postnom_unique');
            });
        } catch (Throwable) {
        }

        try {
            Schema::table('retreat_participant', function (Blueprint $table) {
                $table->unique(['nom', 'prenom'], 'retreat_participant_nom_prenom_unique');
            });
        } catch (Throwable) {
        }
    }
};
