<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $title = 'Règlement d’ordre — inscription retraite (référence système)';

        $exists = DB::table('retreat_policies')->where('title', $title)->exists();
        if ($exists) {
            return;
        }

        $now = now();
        $content = <<<'HTML'
<p><strong>Objet</strong> : conditions générales applicables à toute inscription en ligne à la retraite des jeunes, complétées par les consignes affichées sur place.</p>
<ol>
<li>Vous vous engagez à fournir des informations exactes (identité, coordonnées, participation). Toute fausse déclaration peut entraîner l’annulation de l’inscription.</li>
<li>Le paiement des frais d’inscription, lorsqu’il est requis, valide officiellement la participation selon les modalités indiquées par l’organisation.</li>
<li>Le respect du règlement intérieur, des horaires, des consignes de sécurité et des instructions des responsables est obligatoire pendant toute la durée de la retraite.</li>
<li>Les mineurs sont sous la responsabilité des adultes désignés (tuteur ou accompagnateur) communiqués lors de l’inscription.</li>
<li>Les données collectées sont uniquement pour la gestion de l’événement, l’édition du badge et les communications officielles (E-mail ou message) liées à votre inscription.</li>
</ol>
<p>En poursuivant, vous reconnaissez avoir pris connaissance de ce cadre général ; des politiques additionnelles peuvent vous être présentées par l’équipe d’organisation.</p>
HTML;

        DB::table('retreat_policies')->insert([
            'event_id' => null,
            'category' => 'reglement',
            'title' => $title,
            'content' => $content,
            'target_audience' => 'participant',
            'severity_level' => 3,
            'is_mandatory' => true,
            'is_active' => true,
            'effective_from' => null,
            'effective_to' => null,
            'created_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('retreat_policies')
            ->where('title', 'Règlement d’ordre — inscription retraite (référence système)')
            ->delete();
    }
};
