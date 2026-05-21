<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retreat_participant', function (Blueprint $table): void {
            if (! Schema::hasColumn('retreat_participant', 'family_group_name')) {
                $table->string('family_group_name', 150)->nullable()->after('family_group_id');
            }

            if (! Schema::hasColumn('retreat_participant', 'family_contact_hash')) {
                $table->string('family_contact_hash', 64)->nullable()->after('family_group_name');
                $table->index('family_contact_hash', 'retreat_participant_family_contact_hash_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('retreat_participant', function (Blueprint $table): void {
            if (Schema::hasColumn('retreat_participant', 'family_contact_hash')) {
                $table->dropIndex('retreat_participant_family_contact_hash_idx');
                $table->dropColumn('family_contact_hash');
            }

            if (Schema::hasColumn('retreat_participant', 'family_group_name')) {
                $table->dropColumn('family_group_name');
            }
        });
    }
};
