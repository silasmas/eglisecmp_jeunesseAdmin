<?php

namespace Database\Seeders;

use App\Models\ChurchEvent;
use App\Models\RetreatPolicy;
use App\Models\RetreatRetreatDetail;
use App\Models\RetreatSession;
use Illuminate\Database\Seeder;

/**
 * Données retraite minimales : événement principal, session, détail, politique publique.
 */
class RetreatEssentialSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $event = ChurchEvent::query()->updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Retraite 2026',
                'type' => 'retraite',
                'start_at' => '2026-06-18 00:15:09',
                'end_at' => '2026-06-22 00:15:09',
                'location' => 'Kinshasa',
                'affiche' => null,
                'affiche_id' => null,
                'capacity' => 2000,
                'price_to_pay' => 100.00,
                'currency' => 'CDF',
                'access_auth_mode' => 'otp',
                'access_otp_channel' => 'email',
                'is_active' => false,
            ]
        );

        RetreatSession::query()->updateOrCreate(
            ['id' => 1],
            [
                'title' => 'Jour 1 Matin',
                'start_at' => '2026-05-11 06:00:00',
                'end_at' => '2026-05-11 10:00:00',
                'room' => 'Salle 1',
                'event_id' => $event->id,
                'is_active' => true,
            ]
        );

        RetreatRetreatDetail::query()->updateOrCreate(
            ['id' => 1],
            [
                'theme' => 'Oint moi',
                'speaker' => 'Pasteur Ken Luamba',
                'notes' => 'Dieu va se glorifier',
                'event_id' => $event->id,
                'is_active' => true,
            ]
        );

        $publicPolicyHtml = <<<'HTML'
<p><strong>Objet</strong> : conditions générales applicables à toute inscription en ligne à la retraite des jeunesse, complétées par les consignes affichées sur place.</p>
<ol>
<li>Vous vous engagez à fournir des informations exactes (identité, coordonnées, participation). Toute fausse déclaration peut entraîner l'annulation de l'inscription.</li>
<li>Le paiement des frais d'inscription, lorsqu'il est requis, valide officiellement la participation selon les modalités indiquées par l'organisation.</li>
<li>Le respect du règlement intérieur, des horaires, des consignes de sécurité et des instructions des responsables est obligatoire pendant toute la durée de la retraite.</li>
<li>Les mineurs sont sous la responsabilité des adultes désignés (tuteur ou accompagnateur) communiqués lors de l'inscription.</li>
<li>Les données collectées sont uniquement pour la gestion de l'événement, l'édition du badge et les communications officielles (E-mail ou message) liées à votre inscription.</li>
</ol>
<p>En poursuivant, vous reconnaissez avoir pris connaissance de ce cadre général ; des politiques additionnelles peuvent vous être présentées par l'équipe d'organisation.</p>
HTML;

        RetreatPolicy::query()->updateOrCreate(
            ['id' => 7],
            [
                'event_id' => null,
                'category' => 'reglement',
                'title' => 'Règlement d’ordre — inscription retraite (référence système)',
                'content' => $publicPolicyHtml,
                'target_audience' => 'participant',
                'severity_level' => 3,
                'is_mandatory' => true,
                'is_active' => true,
                'effective_from' => null,
                'effective_to' => null,
                'created_by' => null,
            ]
        );

        $this->command?->info('Retraite : événement #1, session, détail et politique publique #7.');
    }
}
