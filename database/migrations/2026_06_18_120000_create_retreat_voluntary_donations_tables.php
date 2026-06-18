<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dons volontaires retraite (nature ou espèces) et codes parrainage jeunes.
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('retreat_voluntary_donations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events_event')->cascadeOnDelete();
            $table->string('reference', 64)->unique();
            $table->string('donation_kind', 20);
            $table->string('cash_purpose', 32)->nullable();
            $table->string('donor_name', 150);
            $table->string('donor_phone', 30)->nullable();
            $table->string('donor_email', 254)->nullable();
            $table->text('in_kind_description')->nullable();
            $table->unsignedSmallInteger('youth_slots_count')->nullable();
            $table->decimal('amount_expected', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('currency', 8)->default('USD');
            $table->string('payment_channel', 32)->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('status', 32)->default('pending');
            $table->text('donor_message')->nullable();
            $table->boolean('admin_notified')->default(false);
            $table->timestamps();
        });

        Schema::create('retreat_sponsorship_vouchers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('donation_id')->constrained('retreat_voluntary_donations')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events_event')->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->unsignedTinyInteger('uses_total')->default(1);
            $table->unsignedTinyInteger('uses_remaining')->default(1);
            $table->decimal('amount_covered', 12, 2);
            $table->string('currency', 8)->default('USD');
            $table->foreignId('redeemed_by_participant_id')->nullable()->constrained('retreat_participant')->nullOnDelete();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('retreat_sponsorship_vouchers');
        Schema::dropIfExists('retreat_voluntary_donations');
    }
};
