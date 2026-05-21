<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adjoint d'atelier + compte-rendu encadreur par activité et atelier.
     */
    public function up(): void
    {
        Schema::table('retreat_atelier', function (Blueprint $table): void {
            $table->foreignId('adjoint_user_id')
                ->nullable()
                ->after('responsable_user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->index('adjoint_user_id');
        });

        Schema::create('retreat_activity_atelier_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_plan_id')->constrained('retreat_activity_plans')->cascadeOnDelete();
            $table->foreignId('atelier_id')->constrained('retreat_atelier')->cascadeOnDelete();
            $table->string('sujet', 500)->nullable()->comment('Sujet de l\'activité pour cet atelier');
            $table->text('texte_biblique')->nullable()->comment('Texte biblique utilisé');
            $table->json('conducteurs')->nullable()->comment('Conducteurs du débat (ouvrier ou participant)');
            $table->longText('resume')->nullable()->comment('Résumé de l\'activité de l\'atelier');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['activity_plan_id', 'atelier_id'], 'retreat_activity_atelier_report_unique');
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('retreat_activity_atelier_reports');

        Schema::table('retreat_atelier', function (Blueprint $table): void {
            $table->dropForeign(['adjoint_user_id']);
            $table->dropIndex(['adjoint_user_id']);
            $table->dropColumn('adjoint_user_id');
        });
    }
};
