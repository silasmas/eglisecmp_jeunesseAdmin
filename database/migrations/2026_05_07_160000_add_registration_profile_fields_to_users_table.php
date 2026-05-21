<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'role_jeunesse')) {
                $table->string('role_jeunesse', 40)->nullable()->after('fonction_metier');
            }
        });

        if (Schema::hasColumn('users', 'role_participant') && Schema::hasColumn('users', 'role_jeunesse')) {
            DB::table('users')
                ->whereNull('role_jeunesse')
                ->whereNotNull('role_participant')
                ->update(['role_jeunesse' => DB::raw('role_participant')]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'role_jeunesse')) {
                $table->dropColumn('role_jeunesse');
            }
        });
    }
};
