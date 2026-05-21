<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retreat_participant', function (Blueprint $table) {
            $table->uuid('family_group_id')->nullable()->after('event_id')->comment('Regroupe les membres d’un même foyer (liaison tél. urgence / portable)');
            $table->index('family_group_id', 'retreat_participant_family_group_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('retreat_participant', function (Blueprint $table) {
            $table->dropIndex('retreat_participant_family_group_id_idx');
            $table->dropColumn('family_group_id');
        });
    }
};
