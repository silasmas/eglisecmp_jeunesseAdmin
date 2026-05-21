<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supprime les reliquats Django s'ils existent encore dans une base plus ancienne.
        Schema::dropIfExists('django_admin_log');
        Schema::dropIfExists('django_session');
        Schema::dropIfExists('django_migrations');
        Schema::dropIfExists('auth_group_permissions');
        Schema::dropIfExists('authentication_user_user_permissions');
        Schema::dropIfExists('authentication_user_groups');
        Schema::dropIfExists('auth_permission');
        Schema::dropIfExists('auth_group');
        Schema::dropIfExists('django_content_type');
        Schema::dropIfExists('authentication_user');

        // Retire les champs strictement Django devenus redondants avec roles/permissions Laravel.
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_superuser')) {
                $table->dropColumn('is_superuser');
            }
            if (Schema::hasColumn('users', 'is_staff')) {
                $table->dropColumn('is_staff');
            }
        });
    }

    public function down(): void
    {
        // Pas de recreation automatique des tables Django legacy.
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_superuser')) {
                $table->boolean('is_superuser')->default(false)->after('last_login');
            }
            if (! Schema::hasColumn('users', 'is_staff')) {
                $table->boolean('is_staff')->default(false)->after('is_superuser');
            }
        });
    }
};
