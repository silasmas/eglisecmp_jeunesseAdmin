<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Documents consultables sur le billet participant (règlement, histoires à apporter).
     */
    public function up(): void
    {
        Schema::table('events_event', function (Blueprint $table): void {
            $table->string('document_reglement')->nullable()->after('affiche_id');
            $table->string('document_histoires')->nullable()->after('document_reglement');
        });
    }

    /**
     * Supprime les colonnes documents participant.
     */
    public function down(): void
    {
        Schema::table('events_event', function (Blueprint $table): void {
            $table->dropColumn(['document_reglement', 'document_histoires']);
        });
    }
};
