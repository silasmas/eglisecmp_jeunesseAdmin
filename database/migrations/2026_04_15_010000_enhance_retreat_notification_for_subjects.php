<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retreat_notification', function (Blueprint $table) {
            $table->string('title', 150)->nullable()->after('id')->comment('Titre court pour la liste');
            $table->string('category', 30)->default('info')->after('message')->comment('info, success, warning, payment, participant');
            $table->nullableMorphs('subject');
            $table->string('laravel_notification_id', 36)->nullable()->after('user_id')->comment('UUID lie a notifications.id si duplique');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('retreat_notification', function (Blueprint $table) {
            $table->dropMorphs('subject');
            $table->dropColumn([
                'title',
                'category',
                'laravel_notification_id',
            ]);
        });
    }
};
