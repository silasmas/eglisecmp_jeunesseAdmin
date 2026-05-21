<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retreat_activity_plans', function (Blueprint $table) {
            if (Schema::hasColumn('retreat_activity_plans', 'event_id')) {
                $table->dropConstrainedForeignId('event_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('retreat_activity_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('retreat_activity_plans', 'event_id')) {
                $table->foreignId('event_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('events_event');
            }
        });
    }
};
