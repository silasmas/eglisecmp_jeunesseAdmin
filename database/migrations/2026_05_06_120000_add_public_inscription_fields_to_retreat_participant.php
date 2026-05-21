<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retreat_participant', function (Blueprint $table) {
            $table->dropUnique('retreat_participant_nom_prenom_unique');
        });

        Schema::table('retreat_participant', function (Blueprint $table) {
            $table->string('postnom', 100)->nullable()->after('nom');
            $table->date('date_naissance')->nullable()->after('prenom');

            $table->string('commune', 120)->nullable()->after('adresse');
            $table->string('ville', 120)->nullable()->after('commune');

            $table->string('eglise_assemblee', 200)->nullable()->after('observation');
            $table->string('departement_cellule', 150)->nullable()->after('eglise_assemblee');
            $table->string('hebergement_choice', 20)->nullable()->after('departement_cellule');
            $table->string('indicatif_telephone', 10)->nullable()->after('telephone');

            $table->unique(['nom', 'postnom', 'prenom'], 'retreat_participant_identity_unique');
        });
    }

    public function down(): void
    {
        Schema::table('retreat_participant', function (Blueprint $table) {
            $table->dropUnique('retreat_participant_identity_unique');
        });

        Schema::table('retreat_participant', function (Blueprint $table) {
            $table->dropColumn([
                'postnom',
                'date_naissance',
                'ville',
                'commune',
                'eglise_assemblee',
                'departement_cellule',
                'hebergement_choice',
                'indicatif_telephone',
            ]);

            $table->unique(['nom', 'prenom'], 'retreat_participant_nom_prenom_unique');
        });
    }
};
