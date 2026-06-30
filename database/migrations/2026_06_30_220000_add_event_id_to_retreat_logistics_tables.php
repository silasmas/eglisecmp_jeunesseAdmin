<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lie ateliers et chambres à un événement (isolation par édition de retraite).
     */
    public function up(): void
    {
        Schema::table('retreat_atelier', function (Blueprint $table): void {
            $table->foreignId('event_id')
                ->nullable()
                ->after('id')
                ->constrained('events_event')
                ->cascadeOnDelete();
        });

        Schema::table('retreat_chambre', function (Blueprint $table): void {
            $table->foreignId('event_id')
                ->nullable()
                ->after('id')
                ->constrained('events_event')
                ->cascadeOnDelete();
        });

        $this->backfillLogisticsEventIds();

        Schema::table('retreat_atelier', function (Blueprint $table): void {
            $table->dropUnique('retreat_atelier_numero_responsable_unique');
            $table->unique(
                ['event_id', 'numero', 'responsable_user_id'],
                'retreat_atelier_event_numero_responsable_unique'
            );
        });

        Schema::table('retreat_chambre', function (Blueprint $table): void {
            $table->dropUnique('retreat_chambre_nom_sexe_responsable_unique');
            $table->unique(
                ['event_id', 'nom', 'sexe', 'responsable_user_id'],
                'retreat_chambre_event_nom_sexe_responsable_unique'
            );
        });
    }

    /**
     * Remplit event_id à partir des participants ou de l'événement courant.
     */
    private function backfillLogisticsEventIds(): void
    {
        $fallbackEventId = DB::table('events_event')
            ->whereNull('archived_at')
            ->where('is_publicly_closed', false)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->value('id');

        if ($fallbackEventId === null) {
            $fallbackEventId = DB::table('events_event')->orderByDesc('id')->value('id');
        }

        foreach (DB::table('retreat_atelier')->pluck('id') as $atelierId) {
            $eventId = DB::table('retreat_participant')
                ->where('atelier_id', $atelierId)
                ->whereNotNull('event_id')
                ->selectRaw('event_id, COUNT(*) as usage_count')
                ->groupBy('event_id')
                ->orderByDesc('usage_count')
                ->value('event_id');

            DB::table('retreat_atelier')
                ->where('id', $atelierId)
                ->update(['event_id' => $eventId ?? $fallbackEventId]);
        }

        foreach (DB::table('retreat_chambre')->pluck('id') as $chambreId) {
            $eventId = DB::table('retreat_participant')
                ->where('chambre_id', $chambreId)
                ->whereNotNull('event_id')
                ->selectRaw('event_id, COUNT(*) as usage_count')
                ->groupBy('event_id')
                ->orderByDesc('usage_count')
                ->value('event_id');

            DB::table('retreat_chambre')
                ->where('id', $chambreId)
                ->update(['event_id' => $eventId ?? $fallbackEventId]);
        }
    }

    /**
     * Supprime event_id et restaure les contraintes uniques globales.
     */
    public function down(): void
    {
        Schema::table('retreat_atelier', function (Blueprint $table): void {
            $table->dropUnique('retreat_atelier_event_numero_responsable_unique');
            $table->unique(['numero', 'responsable_user_id'], 'retreat_atelier_numero_responsable_unique');
            $table->dropConstrainedForeignId('event_id');
        });

        Schema::table('retreat_chambre', function (Blueprint $table): void {
            $table->dropUnique('retreat_chambre_event_nom_sexe_responsable_unique');
            $table->unique(['nom', 'sexe', 'responsable_user_id'], 'retreat_chambre_nom_sexe_responsable_unique');
            $table->dropConstrainedForeignId('event_id');
        });
    }
};
