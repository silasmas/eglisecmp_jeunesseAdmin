<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events_event', function (Blueprint $table) {
            $table->foreignId('affiche_id')
                ->nullable()
                ->after('affiche')
                ->constrained('media_files')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events_event', function (Blueprint $table) {
            $table->dropConstrainedForeignId('affiche_id');
        });
    }
};
