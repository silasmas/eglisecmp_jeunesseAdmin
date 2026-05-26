<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi d’étape du parcours d’inscription (formulaire → paiement → billet).
 */
return new class extends Migration
{
  public function up(): void
  {
    Schema::table('retreat_participant', function (Blueprint $table): void {
      $table->string('inscription_funnel_stage', 64)
        ->nullable()
        ->after('registration_status')
        ->comment('Dernière étape connue du parcours public (formulaire / paiement)');
      $table->text('inscription_funnel_detail')
        ->nullable()
        ->after('inscription_funnel_stage')
        ->comment('Message lisible pour l’admin (blocage, timeout, etc.)');
      $table->timestamp('inscription_funnel_at')
        ->nullable()
        ->after('inscription_funnel_detail')
        ->comment('Horodatage de la dernière mise à jour du parcours');
      $table->index(['inscription_funnel_stage', 'inscription_funnel_at'], 'retreat_participant_funnel_idx');
    });
  }

  public function down(): void
  {
    Schema::table('retreat_participant', function (Blueprint $table): void {
      $table->dropIndex('retreat_participant_funnel_idx');
      $table->dropColumn([
        'inscription_funnel_stage',
        'inscription_funnel_detail',
        'inscription_funnel_at',
      ]);
    });
  }
};
