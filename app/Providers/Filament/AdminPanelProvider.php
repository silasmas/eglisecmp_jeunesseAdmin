<?php

namespace App\Providers\Filament;

use App\Filament\AvatarProviders\SilhouetteAvatarProvider;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Caresome\FilamentAuthDesigner\AuthDesignerPlugin;
use Caresome\FilamentAuthDesigner\Data\AuthPageConfig;
use Caresome\FilamentAuthDesigner\Enums\MediaPosition;
use Caresome\FilamentAuthDesigner\View\AuthDesignerRenderHook;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use JibayMcs\Tabbed\TabbedPlugin;
use Sanzgrapher\DraggableModal\DraggableModalPlugin;
use Slimani\MediaManager\MediaManagerPlugin;
use Wezlo\FilamentRecordWatcher\FilamentRecordWatcherPlugin;
use Wezlo\FilamentSearchSpotlight\FilamentSearchSpotlightPlugin;
use ZPMLabs\FilamentApiDocsBuilder\FilamentApiDocsBuilderPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->darkMode(true)
            ->defaultThemeMode(ThemeMode::Light)
            ->brandName('CMP Jeunesse — Administration')
            ->brandLogo(asset('retraite-inscription/img/logo.jpg'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('retraite-inscription/img/logo.jpg'))
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->colors([
                'primary' => Color::hex('#D4772C'),
                'gray' => Color::hex('#2D1F17'),
            ])
            ->defaultAvatarProvider(SilhouetteAvatarProvider::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                AuthDesignerPlugin::make()
                    ->defaults(function (AuthPageConfig $config): void {
                        $darkPixel = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='4' height='4'%3E%3Crect fill='%231A1018' width='4' height='4'/%3E%3C/svg%3E";
                        $config
                            ->media($darkPixel, alt: '')
                            ->mediaPosition(MediaPosition::Left)
                            ->mediaSize('44%')
                            ->blur(0)
                            ->renderHook(
                                AuthDesignerRenderHook::MediaOverlay,
                                fn (): string => view('filament.cmp.auth-media-overlay')->render(),
                            );
                    })
                    ->login()
                    ->passwordReset()
                    ->themeToggle(top: '1rem', right: '1rem'),
                DraggableModalPlugin::make(),
                FilamentApiDocsBuilderPlugin::make(),
                FilamentShieldPlugin::make(),
                MediaManagerPlugin::make()
                    ->disk('media')
                    ->navigationGroup('Configuration')
                    ->navigationLabel('Mediatheque')
                    ->navigationIcon('heroicon-o-photo')
                    ->navigationSort(95),
                FilamentSearchSpotlightPlugin::make()
                    ->keyBinding('mod+k')
                    ->placeholder('Rechercher dans l\'administration...')
                    ->resultLimitPerCategory(8),
                FilamentRecordWatcherPlugin::make()
                    ->navigationGroup('Notifications')
                    ->navigationIcon('heroicon-o-bell-alert'),
                TabbedPlugin::make()
                    ->defaultPage('edit')
                    ->persistKey('admin_record_tabs')
                    ->confirmClose()
                    ->keyboardShortcuts(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->assets([
                Css::make('filament-cmp-theme', asset('css/filament-cmp-theme.css')),
                Css::make('filament-retreat-groups', asset('css/filament-retreat-groups.css')),
                Css::make('media-manager-plain-compiled', asset('css/media-manager-plain.css')),
                Css::make('filament-rich-select-badges', asset('css/filament-rich-select-badges.css')),
            ], 'app')
            ->bootUsing(function (Panel $panel): void {
                if ($panel->getId() === 'admin') {
                    app()->setLocale('fr');
                }
            });
    }
}
