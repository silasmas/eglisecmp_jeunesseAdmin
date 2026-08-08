<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

/**
 * Exemples de modèles SMS pour campagnes retraite.
 */
class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Rappel retraite',
                'slug' => 'rappel-retraite',
                'description' => 'Rappel général avant le début de la retraite.',
                'body' => 'Bonjour {{prenom}}, rappel : {{evenement}}. Info : eglisecmp.com',
                'is_active' => true,
            ],
            [
                'name' => 'Rappel billet',
                'slug' => 'rappel-billet',
                'description' => 'Rappel avec lien billet (paiement validé requis).',
                'body' => 'CMP {{prenom}}, ton billet : {{lien_billet}}',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            SmsTemplate::query()->updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}
