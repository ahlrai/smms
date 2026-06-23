<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')

            // AUTH
            ->login()
            ->passwordReset()

            // SIDEBAR
            ->sidebarWidth('16rem')

            // WARNA
            ->colors([
                'primary' => Color::hex('#14c8aa'),
            ])

            // RESOURCE
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources'
            )

            // PAGE — discoverPages menemukan semua halaman di app/Filament/Pages/
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages'
            )

            ->pages([
                Dashboard::class,
            ])

            // Widget global dikosongkan — masing-masing page mengelola widgetnya sendiri:
            // Dashboard   → App\Filament\Pages\Dashboard::getWidgets()
            // Analytics   → App\Filament\Pages\AnalyticsPage::getHeaderWidgets()
            ->widgets([])

            ->viteTheme('resources/css/filament/admin/theme.css')

            // MIDDLEWARE
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])

            // AUTH MIDDLEWARE
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
