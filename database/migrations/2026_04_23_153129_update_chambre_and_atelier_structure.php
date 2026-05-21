<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('retreat_chambre', function (Blueprint $table) {
            $table->text('description')->nullable()->after('role_on_chambre');
            $table->longText('rapport_final')->nullable()->after('description');

            $table->dropForeign(['owner_id']);
            $table->dropIndex(['owner_id']);
            $table->dropUnique('retreat_chambre_nom_sexe_owner_unique');
            $table->dropColumn('owner_id');
        });

        Schema::table('retreat_chambre', function (Blueprint $table) {
            $table->unique(['nom', 'sexe', 'responsable_user_id'], 'retreat_chambre_nom_sexe_responsable_unique');
        });

        Schema::table('retreat_atelier', function (Blueprint $table) {
            $table->text('description')->nullable()->after('role_on_atelier');
            $table->longText('rapport_final')->nullable()->after('description');

            $table->dropForeign(['owner_id']);
            $table->dropIndex(['owner_id']);
            $table->dropUnique('retreat_atelier_numero_owner_unique');
            $table->dropColumn('owner_id');
        });

        Schema::table('retreat_atelier', function (Blueprint $table) {
            $table->unique(['numero', 'responsable_user_id'], 'retreat_atelier_numero_responsable_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retreat_chambre', function (Blueprint $table) {
            $table->dropUnique('retreat_chambre_nom_sexe_responsable_unique');
            $table->foreignId('owner_id')->nullable()->after('sexe')->constrained('users');
            $table->index('owner_id');
            $table->unique(['nom', 'sexe', 'owner_id'], 'retreat_chambre_nom_sexe_owner_unique');
            $table->dropColumn(['description', 'rapport_final']);
        });

        Schema::table('retreat_atelier', function (Blueprint $table) {
            $table->dropUnique('retreat_atelier_numero_responsable_unique');
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('users');
            $table->index('owner_id');
            $table->unique(['numero', 'owner_id'], 'retreat_atelier_numero_owner_unique');
            $table->dropColumn(['description', 'rapport_final']);
        });
    }
};
