<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * Crée le lien symbolique public/storage → storage/app/public (équivalent storage:link).
 */
class StorageLinkService
{
    /**
     * Exécute la commande Artisan storage:link.
     *
     * @return array{success: bool, message: string} Résultat et sortie console
     */
    public function run(): array
    {
        $exitCode = Artisan::call('storage:link');
        $message = trim(Artisan::output());

        if ($message === '') {
            $message = $exitCode === SymfonyCommand::SUCCESS
                ? 'Lien symbolique storage créé.'
                : 'La commande storage:link a échoué.';
        }

        return [
            'success' => $exitCode === SymfonyCommand::SUCCESS,
            'message' => $message,
        ];
    }
}
