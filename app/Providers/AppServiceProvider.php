<?php

namespace App\Providers;

use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Observers\RetreatParticipantObserver;
use App\Observers\RetreatPaymentObserver;
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
        RetreatParticipant::observe(RetreatParticipantObserver::class);
        RetreatPayment::observe(RetreatPaymentObserver::class);

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
}
