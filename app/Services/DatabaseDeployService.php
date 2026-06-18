<?php

namespace App\Services;

use Illuminate\Database\Migrations\Migrator;
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
     * Exécute les migrations en attente une par une.
     * En cas d'erreur « déjà existant », enregistre la migration et continue.
     * Les autres erreurs sont signalées mais n'empêchent pas les suivantes.
     *
     * @return array{
     *   success: bool,
     *   partial: bool,
     *   message: string,
     *   applied: list<string>,
     *   skipped: list<string>,
     *   failed: list<string>
     * }
     */
    public function runMigrations(): array
    {
        try {
            /** @var Migrator $migrator */
            $migrator = app('migrator');
            $paths = [database_path('migrations')];
            $files = $migrator->getMigrationFiles($paths);
            $repository = $migrator->getRepository();
            $pending = array_values(array_diff(array_keys($files), $repository->getRan()));

            if ($pending === []) {
                return $this->migrationResult(
                    applied: [],
                    skipped: [],
                    failed: [],
                    extraMessage: 'Aucune migration en attente.'
                );
            }

            $applied = [];
            $skipped = [];
            $failed = [];
            $skipBatch = $repository->getNextBatchNumber();

            foreach ($pending as $migration) {
                $fullPath = $files[$migration];

                try {
                    $migrator->run([$fullPath]);
                    $applied[] = $migration;
                } catch (Throwable $e) {
                    $output = $e->getMessage();

                    if ($this->migrationTargetAlreadyExists($output)) {
                        if (! in_array($migration, $repository->getRan(), true)) {
                            $repository->log($migration, $skipBatch);
                        }

                        $skipped[] = $migration;

                        continue;
                    }

                    report($e);
                    $failed[] = "{$migration} — {$output}";
                }
            }

            return $this->migrationResult($applied, $skipped, $failed);
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'partial' => false,
                'message' => $e->getMessage(),
                'applied' => [],
                'skipped' => [],
                'failed' => [],
            ];
        }
    }

    /**
     * Migrations puis seeders de base (Shield, admin, SMS, retraite).
     * La sync s'exécute même si certaines migrations ont échoué (sauf exception fatale).
     *
     * @return array{success: bool, partial: bool, message: string, applied: list<string>, skipped: list<string>, failed: list<string>}
     */
    public function runMigrationsAndSyncBase(): array
    {
        $migrationResult = $this->runMigrations();

        $isFatalException = $migrationResult['success'] === false
            && ($migrationResult['partial'] ?? false) === false
            && $migrationResult['applied'] === []
            && $migrationResult['skipped'] === []
            && $migrationResult['failed'] === [];

        if ($isFatalException) {
            return $migrationResult;
        }

        try {
            $this->baseDataSync->run();
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'partial' => $migrationResult['partial'] || $migrationResult['success'],
                'message' => $migrationResult['message']."\n\nÉchec de la synchronisation : {$e->getMessage()}",
                'applied' => $migrationResult['applied'],
                'skipped' => $migrationResult['skipped'],
                'failed' => $migrationResult['failed'],
            ];
        }

        $syncLine = "\n\nDonnées de base et rôles Shield synchronisés.";

        return [
            'success' => $migrationResult['failed'] === [],
            'partial' => $migrationResult['failed'] !== [] || $migrationResult['skipped'] !== [],
            'message' => $migrationResult['message'].$syncLine,
            'applied' => $migrationResult['applied'],
            'skipped' => $migrationResult['skipped'],
            'failed' => $migrationResult['failed'],
        ];
    }

    /**
     * @param list<string> $applied Migrations appliquées
     * @param list<string> $skipped Migrations ignorées (schéma déjà présent)
     * @param list<string> $failed Migrations en échec
     * @param string|null $extraMessage Message additionnel
     * @return array{success: bool, partial: bool, message: string, applied: list<string>, skipped: list<string>, failed: list<string>}
     */
    protected function migrationResult(array $applied, array $skipped, array $failed, ?string $extraMessage = null): array
    {
        $lines = [];

        foreach ($applied as $name) {
            $lines[] = "✓ {$name}";
        }

        foreach ($skipped as $name) {
            $lines[] = "⊘ {$name} (déjà présent, migration enregistrée)";
        }

        foreach ($failed as $detail) {
            $lines[] = "✗ {$detail}";
        }

        if ($extraMessage !== null) {
            array_unshift($lines, $extraMessage);
        }

        $message = $lines !== [] ? implode("\n", $lines) : 'Migrations traitées.';

        return [
            'success' => $failed === [],
            'partial' => $failed !== [] || $skipped !== [],
            'message' => $message,
            'applied' => $applied,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    /**
     * Détecte une erreur SQL « déjà existant » (colonne, table, index, clé).
     *
     * @param string $output Message d'erreur
     * @return bool True si le schéma semble déjà à jour
     */
    protected function migrationTargetAlreadyExists(string $output): bool
    {
        $needles = [
            'Duplicate column name',
            'Duplicate key name',
            'Duplicate foreign key constraint name',
            'Duplicate entry',
            'already exists',
            'Base table or view already exists',
            '42S21',
            '42S01',
            '23000',
            '1060',
            '1061',
            '1050',
            'errno: 121',
            '1826',
        ];

        foreach ($needles as $needle) {
            if (stripos($output, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
