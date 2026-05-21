<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('nom', 100)->nullable()->after('name');
            $table->string('postnom', 100)->nullable()->after('nom');
            $table->string('prenom', 100)->nullable()->after('postnom');
            $table->string('sexe', 10)->nullable()->after('prenom');
            $table->date('date_naissance')->nullable()->after('sexe');
            $table->string('role_participant', 80)->nullable()->after('date_naissance');

            $table->string('indicatif_telephone', 10)->nullable()->after('role_participant');
            $table->string('telephone', 30)->nullable()->after('indicatif_telephone');
            $table->string('telephone_urgence', 30)->nullable()->after('telephone');
            $table->string('guardian_name', 150)->nullable()->after('telephone_urgence');
            $table->string('guardian_phone', 30)->nullable()->after('guardian_name');
            $table->string('adresse', 255)->nullable()->after('guardian_phone');
            $table->string('commune', 120)->nullable()->after('adresse');
            $table->string('ville', 120)->nullable()->after('commune');

            $table->string('eglise_assemblee', 200)->nullable()->after('ville');
            $table->string('departement_cellule', 150)->nullable()->after('eglise_assemblee');
            $table->string('hebergement_choice', 10)->nullable()->after('departement_cellule');
            $table->text('observation')->nullable()->after('hebergement_choice');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'nom',
                'postnom',
                'prenom',
                'sexe',
                'date_naissance',
                'role_participant',
                'indicatif_telephone',
                'telephone',
                'telephone_urgence',
                'guardian_name',
                'guardian_phone',
                'adresse',
                'commune',
                'ville',
                'eglise_assemblee',
                'departement_cellule',
                'hebergement_choice',
                'observation',
            ]);
        });
    }
};
