<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\DatabaseDeployService;
use App\Services\ProductionBaseDataSyncService;
use App\Services\StorageLinkService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Throwable;
use UnitEnum;

/**
 * Page admin pour relancer la synchronisation des données de base et Shield.
 */
class SyncProductionBaseData extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Sync données de base';

    protected static ?string $title = 'Synchronisation données de base';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 99;

    protected static ?string $slug = 'sync-donnees-base';

    protected string $view = 'filament.pages.sync-production-base-data';

    /**
     * @param array<string, mixed> $parameters Paramètres de route Filament
     * @return bool Accès réservé au super_admin
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole('super_admin');
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncMigrationsResilient')
                ->label('Synchroniser les migrations')
                ->icon('heroicon-o-circle-stack')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Synchroniser les migrations ?')
                ->modalDescription(
                    'Exécute chaque migration en attente une par une. '
                    .'Si une table ou colonne existe déjà (erreur « Duplicate column », etc.), '
                    .'la migration est enregistrée comme appliquée et le processus continue avec les suivantes — '
                    .'y compris les migrations récemment ajoutées.'
                )
                ->modalSubmitActionLabel('Synchroniser')
                ->action(function (): void {
                    $this->notifyMigrationResult(
                        app(DatabaseDeployService::class)->runMigrations(),
                        'Synchronisation des migrations'
                    );
                }),
            Action::make('syncProductionBase')
                ->label('Mettre à jour maintenant')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Synchroniser les données de base ?')
                ->modalDescription(
                    'Cette action met à jour les permissions Shield, les rôles (super_admin, panel_user, ouvrier), '
                    .'l’utilisateur admin, l’opérateur SMS Keccel et les données retraite essentielles. '
                    .'Les données existantes ne sont pas effacées (firstOrCreate / sync).'
                )
                ->modalSubmitActionLabel('Synchroniser')
                ->action(function (): void {
                    try {
                        app(ProductionBaseDataSyncService::class)->run();
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('Échec de la synchronisation')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Synchronisation terminée')
                        ->body('Données de base et rôles Shield mis à jour.')
                        ->success()
                        ->send();
                }),
            Action::make('migrateAndSync')
                ->label('Migrations + sync')
                ->icon('heroicon-o-server-stack')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Exécuter les migrations et synchroniser ?')
                ->modalDescription(
                    'Exécute php artisan migrate --force puis la synchronisation des données de base '
                    .'(permissions Shield, rôles, admin, SMS, retraite). À utiliser après un déploiement '
                    .'pour appliquer les nouvelles colonnes et tables (badge, fenêtre pointage, etc.).'
                )
                ->modalSubmitActionLabel('Exécuter')
                ->action(function (): void {
                    try {
                        $result = app(DatabaseDeployService::class)->runMigrationsAndSyncBase();
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('Échec migrations + sync')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->notifyMigrationResult($result, 'Migrations et sync');
                }),
            Action::make('storageLink')
                ->label('Lien storage')
                ->icon('heroicon-o-link')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Créer le lien storage ?')
                ->modalDescription(
                    'Exécute php artisan storage:link : lie public/storage à storage/app/public '
                    .'pour servir les fichiers uploadés (médias, pièces jointes).'
                )
                ->modalSubmitActionLabel('Exécuter storage:link')
                ->action(function (): void {
                    try {
                        $result = app(StorageLinkService::class)->run();
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('Échec storage:link')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $notification = Notification::make()
                        ->title($result['success'] ? 'storage:link terminé' : 'storage:link')
                        ->body($result['message']);

                    if ($result['success']) {
                        $notification->success()->send();

                        return;
                    }

                    $notification->warning()->send();
                }),
        ];
    }

    /**
     * URL HTTP signée par token (si PRODUCTION_BASE_SYNC_TOKEN est défini).
     *
     * @return string|null Lien complet ou null si token absent
     */
    public function getHttpSyncUrl(): ?string
    {
        $token = config('cmp.production_base_sync_token');

        if (! is_string($token) || $token === '') {
            return null;
        }

        return route('system.sync-production-base', ['token' => $token]);
    }

    /**
     * Affiche une notification Filament selon le résultat des migrations.
     *
     * @param array{success: bool, partial?: bool, message: string, applied?: list<string>, skipped?: list<string>, failed?: list<string>} $result
     * @param string $titlePrefix Préfixe du titre
     * @return void
     */
    protected function notifyMigrationResult(array $result, string $titlePrefix): void
    {
        $summary = [];
        if (! empty($result['applied'])) {
            $summary[] = count($result['applied']).' appliquée(s)';
        }
        if (! empty($result['skipped'])) {
            $summary[] = count($result['skipped']).' contournée(s)';
        }
        if (! empty($result['failed'])) {
            $summary[] = count($result['failed']).' en échec';
        }

        $titleSuffix = $result['success']
            ? 'terminée'
            : (($result['partial'] ?? false) ? 'terminée avec avertissements' : 'échouée');

        $body = $result['message'];
        if ($summary !== []) {
            $body = implode(' · ', $summary)."\n\n".$body;
        }

        $notification = Notification::make()
            ->title("{$titlePrefix} {$titleSuffix}")
            ->body($body);

        if ($result['success']) {
            $notification->success()->send();

            return;
        }

        if ($result['partial'] ?? false) {
            $notification->warning()->send();

            return;
        }

        $notification->danger()->send();
    }
}
