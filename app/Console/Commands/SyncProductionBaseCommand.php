<?php

namespace App\Console\Commands;

use App\Services\ProductionBaseDataSyncService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Commande Artisan pour synchroniser données de base et RBAC Shield.
 */
class SyncProductionBaseCommand extends Command
{
    protected $signature = 'cmp:sync-production-base';

    protected $description = 'Synchronise les données de base (Shield, admin, SMS, retraite)';

    /**
     * @return int Code de sortie
     */
    public function handle(ProductionBaseDataSyncService $syncService): int
    {
        try {
            $syncService->run();
        } catch (Throwable $e) {
            $this->error('Échec : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Données de base et rôles Shield synchronisés.');

        return self::SUCCESS;
    }
}
