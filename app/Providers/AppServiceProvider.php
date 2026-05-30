<?php

namespace App\Providers;

use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Observers\RetreatAtelierObserver;
use App\Observers\RetreatChambreObserver;
use App\Observers\RetreatParticipantObserver;
use App\Observers\RetreatPaymentObserver;
use App\Services\StoragePathService;
use Filament\Forms\Components\FileUpload;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureS3DiskUrls();

        $storagePaths = app(StoragePathService::class);

        FileUpload::configureUsing(function (FileUpload $component) use ($storagePaths): void {
            $component
                ->disk($storagePaths->uploadDisk())
                ->getUploadedFileNameForStorageUsing(
                    fn ($file): string => $storagePaths->uniqueFilename($file)
                );

            if ($storagePaths->uploadDisk() !== 's3') {
                $component->visibility('public');
            }
        });

        RetreatParticipant::observe(RetreatParticipantObserver::class);
        RetreatPayment::observe(RetreatPaymentObserver::class);
        RetreatAtelier::observe(RetreatAtelierObserver::class);
        RetreatChambre::observe(RetreatChambreObserver::class);

        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
            fn (): string => view('filament.cmp.login-brand')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('filament.cmp.panel-styles')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn (): string => view('filament.cmp.preloader')->render(),
        );
    }

    /**
     * Définit l’URL publique de base des disques S3 si AWS_URL est vide.
     *
     * @return void
     */
    private function configureS3DiskUrls(): void
    {
        foreach (['s3', 'media', 'public'] as $diskName) {
            $disk = config("filesystems.disks.{$diskName}");

            if (($disk['driver'] ?? null) !== 's3') {
                continue;
            }

            if (! empty($disk['url'])) {
                continue;
            }

            $bucket = $disk['bucket'] ?? null;
            $region = $disk['region'] ?? env('AWS_DEFAULT_REGION');

            if (! is_string($bucket) || $bucket === '' || ! is_string($region) || $region === '') {
                continue;
            }

            config([
                "filesystems.disks.{$diskName}.url" => "https://{$bucket}.s3.{$region}.amazonaws.com",
            ]);
        }
    }
}
