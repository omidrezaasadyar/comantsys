<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Customer-facing portal panel — intentionally EMPTY.
 *
 * Purpose right now is only to prove the login door works. It therefore
 * discovers nothing: no resources, no pages, no widgets, no plugins, and no
 * custom theme/colors (stock Filament look).
 *
 * The id 'portal' is load-bearing: User::canAccessPanel() branches on exactly
 * this string, so renaming it here silently changes who can log in.
 *
 * Deliberately NOT ->default() — AdminPanelProvider holds that, and only one
 * panel may be the default.
 */
class PortalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('portal')
            ->path('portal')
            ->login()
            // Same guard as admin: one users table, one session; the two
            // audiences are separated by canAccessPanel(), not by guard.
            ->authGuard('web')
            // Scoped to the portal's own tree only. Admin scans
            // app/Filament/Resources, which does not contain this path, so the
            // two panels cannot pick up each other's resources.
            // Deliberately no discoverPages()/discoverWidgets() yet.
            ->discoverResources(in: app_path('Filament/Portal/Resources'), for: 'App\Filament\Portal\Resources')
            // Middleware mirrors AdminPanelProvider so sessions/CSRF/auth
            // behave identically on this panel.
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
