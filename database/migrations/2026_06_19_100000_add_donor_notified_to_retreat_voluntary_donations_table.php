<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi de l'e-mail de confirmation envoyé au donateur.
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('retreat_voluntary_donations', function (Blueprint $table): void {
            $table->boolean('donor_notified')->default(false)->after('admin_notified');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('retreat_voluntary_donations', function (Blueprint $table): void {
            $table->dropColumn('donor_notified');
        });
    }
};
