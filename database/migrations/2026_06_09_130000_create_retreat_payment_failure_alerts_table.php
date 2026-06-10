<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table des alertes d'échec de paiement d'inscription.
     */
    public function up(): void
    {
        Schema::create('retreat_payment_failure_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('retreat_payment_id')->nullable()->constrained('retreat_payments')->nullOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained('retreat_participant')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events_event')->nullOnDelete();
            $table->string('reference', 64)->index();
            $table->string('channel', 32)->nullable();
            $table->string('failure_reason', 64);
            $table->string('failure_source', 64);
            $table->text('message');
            $table->json('technical_detail')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->string('email_recipient')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['acknowledged_at', 'created_at']);
        });
    }

    /**
     * Supprime la table des alertes d'échec de paiement.
     */
    public function down(): void
    {
        Schema::dropIfExists('retreat_payment_failure_alerts');
    }
};
