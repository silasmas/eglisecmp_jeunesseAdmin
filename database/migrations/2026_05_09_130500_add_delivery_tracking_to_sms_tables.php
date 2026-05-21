<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_operators', function (Blueprint $table): void {
            $table->string('delivery_url')->nullable()->after('balance_url');
        });

        Schema::table('sms_message_logs', function (Blueprint $table): void {
            $table->string('delivery_status', 40)->nullable()->after('status');
            $table->timestamp('delivery_checked_at')->nullable()->after('sent_at');
            $table->text('delivery_response')->nullable()->after('provider_response');
        });

        DB::table('sms_operators')
            ->where('provider', 'keccel')
            ->update([
                'balance_url' => config('services.sms.balance_url'),
                'delivery_url' => config('services.sms.delivery_url'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('sms_message_logs', function (Blueprint $table): void {
            $table->dropColumn(['delivery_status', 'delivery_checked_at', 'delivery_response']);
        });

        Schema::table('sms_operators', function (Blueprint $table): void {
            $table->dropColumn('delivery_url');
        });
    }
};
