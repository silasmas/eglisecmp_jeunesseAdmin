<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reg_form_field_items', function (Blueprint $table) {
            $table->boolean('is_admin_unlocked')->default(false)->after('column_span');
            $table->string('label_override', 200)->nullable()->after('is_admin_unlocked');
            $table->string('helper_text_override', 500)->nullable()->after('label_override');
        });
    }

    public function down(): void
    {
        Schema::table('reg_form_field_items', function (Blueprint $table) {
            $table->dropColumn([
                'is_admin_unlocked',
                'label_override',
                'helper_text_override',
            ]);
        });
    }
};
