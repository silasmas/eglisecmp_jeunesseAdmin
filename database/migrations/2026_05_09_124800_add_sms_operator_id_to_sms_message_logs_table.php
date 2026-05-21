<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_message_logs', function (Blueprint $table): void {
            $table->foreignId('sms_operator_id')
                ->nullable()
                ->after('id')
                ->constrained('sms_operators')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sms_message_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sms_operator_id');
        });
    }
};
