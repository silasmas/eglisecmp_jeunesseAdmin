<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Choix du logo au centre du QR code (Jeunesse ou CMP).
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('generated_qr_codes', function (Blueprint $table): void {
            $table->string('logo_key', 32)->default('jeunesse')->after('embed_logo');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('generated_qr_codes', function (Blueprint $table): void {
            $table->dropColumn('logo_key');
        });
    }
};
