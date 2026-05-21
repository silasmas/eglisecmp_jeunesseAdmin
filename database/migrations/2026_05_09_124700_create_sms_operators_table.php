<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_operators', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('provider', 40)->default('keccel');
            $table->string('send_url');
            $table->string('balance_url')->nullable();
            $table->string('token');
            $table->string('sender', 50);
            $table->string('send_method', 10)->default('POST');
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('remaining_sms')->nullable();
            $table->timestamp('last_balance_checked_at')->nullable();
            $table->text('last_balance_response')->nullable();
            $table->timestamps();

            $table->index(['provider', 'is_active']);
        });

        if (filled(config('services.sms.url')) && filled(config('services.sms.token'))) {
            DB::table('sms_operators')->insert([
                'name' => 'Keccel',
                'provider' => 'keccel',
                'send_url' => config('services.sms.url'),
                'balance_url' => config('services.sms.balance_url'),
                'token' => trim((string) config('services.sms.token')),
                'sender' => trim((string) config('services.sms.from', 'CMP')),
                'send_method' => 'POST',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_operators');
    }
};
