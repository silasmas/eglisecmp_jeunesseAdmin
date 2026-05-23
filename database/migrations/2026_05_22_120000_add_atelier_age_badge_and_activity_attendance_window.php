<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tranches d'âge atelier, badge participant, fenêtre de pointage activité.
     */
    public function up(): void
    {
        Schema::table('retreat_atelier', function (Blueprint $table): void {
            $table->unsignedTinyInteger('age_min')->nullable()->after('numero')
                ->comment('Âge minimum inclus pour affectation automatique');
            $table->unsignedTinyInteger('age_max')->nullable()->after('age_min')
                ->comment('Âge maximum inclus pour affectation automatique');
        });

        Schema::table('retreat_participant', function (Blueprint $table): void {
            $table->boolean('badge_received')->default(false)->after('billet_envoye_whatsapp')
                ->comment('Badge physique remis au participant');
            $table->dateTime('badge_received_at')->nullable()->after('badge_received');
        });

        Schema::table('retreat_activity_plans', function (Blueprint $table): void {
            $table->unsignedSmallInteger('attendance_window_minutes')->default(30)->after('is_mandatory')
                ->comment('Durée (min) après le début pour marquer les présences');
            $table->dateTime('attendance_reminder_sent_at')->nullable()->after('attendance_window_minutes');
            $table->dateTime('attendance_overdue_notified_at')->nullable()->after('attendance_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('retreat_activity_plans', function (Blueprint $table): void {
            $table->dropColumn([
                'attendance_window_minutes',
                'attendance_reminder_sent_at',
                'attendance_overdue_notified_at',
            ]);
        });

        Schema::table('retreat_participant', function (Blueprint $table): void {
            $table->dropColumn(['badge_received', 'badge_received_at']);
        });

        Schema::table('retreat_atelier', function (Blueprint $table): void {
            $table->dropColumn(['age_min', 'age_max']);
        });
    }
};
