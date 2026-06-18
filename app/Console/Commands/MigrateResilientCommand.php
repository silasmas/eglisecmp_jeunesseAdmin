<?php

namespace App\Console\Commands;

use App\Services\DatabaseDeployService;
use Illuminate\Console\Command;

/**
 * Commande Artisan : migrations une par une avec contournement des conflits schéma.
 */
class MigrateResilientCommand extends Command
{
    protected $signature = 'cmp:migrate-resilient';

    protected $description = 'Exécute les migrations en attente ; ignore les conflits « déjà existant » et continue';

    /**
     * @return int Code de sortie
     */
    public function handle(DatabaseDeployService $deployService): int
    {
        $result = $deployService->runMigrations();

        $this->line($result['message']);

        if ($result['success']) {
            $this->info('Synchronisation des migrations terminée.');

            return self::SUCCESS;
        }

        if ($result['partial'] && $result['applied'] !== []) {
            $this->warn('Terminé avec avertissements — consultez les lignes ✗ ci-dessus.');

            return self::SUCCESS;
        }

        $this->error('Échec de la synchronisation des migrations.');

        return self::FAILURE;
    }
}
