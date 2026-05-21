<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_message_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 40)->default('keccel');
            $table->string('context', 80)->nullable();
            $table->string('sender', 50)->nullable();
            $table->string('recipient', 30);
            $table->text('message');
            $table->string('status', 30)->default('pending');
            $table->string('http_method', 10)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('provider_reference', 120)->nullable();
            $table->text('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'status']);
            $table->index(['recipient', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_message_logs');
    }
};
