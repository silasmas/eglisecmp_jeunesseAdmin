<?php

namespace App\Services;

use Illuminate\Database\Migrations\Migrator;
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
     * Exécute les migrations en attente une par une ; ignore celles déjà appliquées au schéma.
     *
     * @return array{success: bool, message: string}
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
                return [
                    'success' => true,
                    'message' => 'Aucune migration en attente.',
                ];
            }

            $lines = [];
            $failures = [];
            $skipBatch = $repository->getNextBatchNumber();

            foreach ($pending as $migration) {
                $relativePath = $this->migrationRelativePath($files[$migration]);

                $exitCode = Artisan::call('migrate', [
                    '--force' => true,
                    '--path' => $relativePath,
                ]);
                $output = trim(Artisan::output());

                if ($exitCode === SymfonyCommand::SUCCESS) {
                    $lines[] = "✓ {$migration}";

                    continue;
                }

                if ($this->migrationTargetAlreadyExists($output)) {
                    if (! in_array($migration, $repository->getRan(), true)) {
                        $repository->log($migration, $skipBatch);
                    }

                    $lines[] = "⊘ {$migration} (schéma déjà présent, migration enregistrée)";

                    continue;
                }

                $failures[] = "✗ {$migration}\n{$output}";
            }

            $message = implode("\n", array_merge($lines, $failures));

            if ($message === '') {
                $message = 'Migrations traitées.';
            }

            return [
                'success' => $failures === [],
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

    /**
     * Chemin relatif d'un fichier de migration pour artisan migrate --path.
     *
     * @param string $fullPath Chemin absolu du fichier
     * @return string Chemin relatif à la racine du projet
     */
    protected function migrationRelativePath(string $fullPath): string
    {
        $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';

        return str_replace($base, '', str_replace('\\', '/', $fullPath));
    }

    /**
     * Détecte une erreur SQL « déjà existant » (colonne, table, index, clé).
     *
     * @param string $output Sortie console de la migration
     * @return bool True si le schéma semble déjà à jour
     */
    protected function migrationTargetAlreadyExists(string $output): bool
    {
        $needles = [
            'Duplicate column name',
            'Duplicate key name',
            'already exists',
            'Base table or view already exists',
            '42S01',
            '42S21',
            '1060',
            '1061',
            '1050',
        ];

        foreach ($needles as $needle) {
            if (stripos($output, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
