<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Throwable;

/**
 * Déploiement schéma BDD (migrations) puis synchronisation des données de base.
 */
class DatabaseDeployService
{
    public function __construct(
        protected ProductionBaseDataSyncService $baseDataSync,
    ) {}

    /**
     * Exécute les migrations en attente (--force).
     *
     * @return array{success: bool, message: string}
     */
    public function runMigrations(): array
    {
        try {
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());
            $message = $output !== '' ? $output : 'Migrations exécutées.';

            return [
                'success' => $exitCode === SymfonyCommand::SUCCESS,
                'message' => $message,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Migrations puis seeders de base (Shield, admin, SMS, retraite).
     *
     * @return array{success: bool, message: string}
     */
    public function runMigrationsAndSyncBase(): array
    {
        $migrationResult = $this->runMigrations();

        if (! $migrationResult['success']) {
            return $migrationResult;
        }

        try {
            $this->baseDataSync->run();
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => "Migrations OK, mais échec de la synchronisation : {$e->getMessage()}",
            ];
        }

        return [
            'success' => true,
            'message' => $migrationResult['message']."\n\nDonnées de base et rôles Shield synchronisés.",
        ];
    }
}
