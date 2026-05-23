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

                    $notification = Notification::make()
                        ->title($result['success'] ? 'Migrations et sync terminées' : 'Échec')
                        ->body($result['message']);

                    if ($result['success']) {
                        $notification->success()->send();

                        return;
                    }

                    $notification->danger()->send();
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
}
