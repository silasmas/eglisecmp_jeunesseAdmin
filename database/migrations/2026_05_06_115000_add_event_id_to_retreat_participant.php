<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retreat_participant', function (Blueprint $table) {
            $table->foreignId('event_id')
                ->nullable()
                ->after('id')
                ->constrained('events_event')
                ->nullOnDelete();
            $table->index(['event_id', 'registration_status'], 'retreat_participant_event_reg_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('retreat_participant', function (Blueprint $table) {
            $table->dropIndex('retreat_participant_event_reg_status_idx');
            $table->dropConstrainedForeignId('event_id');
        });
    }
};
