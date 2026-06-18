<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preuve de paiement cash et validation admin pour les dons espèces.
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('retreat_voluntary_donations', function (Blueprint $table): void {
            $table->string('cash_proof_path')->nullable()->after('provider_reference');
            $table->timestamp('cash_validated_at')->nullable()->after('cash_proof_path');
            $table->foreignId('cash_validated_by')->nullable()->after('cash_validated_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('retreat_voluntary_donations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cash_validated_by');
            $table->dropColumn(['cash_proof_path', 'cash_validated_at']);
        });
    }
};
