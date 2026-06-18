<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QR codes générés depuis l'admin (lien cible + logo optionnel).
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('generated_qr_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('target_url', 2048);
            $table->boolean('embed_logo')->default(false);
            $table->string('file_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_qr_codes');
    }
};
