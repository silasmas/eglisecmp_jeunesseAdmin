<?php

namespace App\Console\Commands;

use App\Services\DatabaseDeployService;
use Illuminate\Console\Command;

/**
 * Commande Artisan : migrations + synchronisation données de base.
 */
class MigrateAndSyncProductionCommand extends Command
{
    protected $signature = 'cmp:migrate-and-sync';

    protected $description = 'Exécute les migrations puis synchronise les données de base (Shield, admin, SMS, retraite)';

    /**
     * @return int Code de sortie
     */
    public function handle(DatabaseDeployService $deployService): int
    {
        $result = $deployService->runMigrationsAndSyncBase();

        if (! $result['success']) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->line($result['message']);

        return self::SUCCESS;
    }
}
