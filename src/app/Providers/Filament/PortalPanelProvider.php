<?php

namespace App\Providers\Filament;

use Filament\FontProviders\LocalFontProvider;
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
 * Customer-facing portal panel — intentionally minimal.
 *
 * It carries the PortalRequests resource and nothing else: no pages, no
 * widgets, no plugins, and no ->viteTheme()/->colors() (the panel chrome is
 * stock Filament apart from the font). The LOGIN page is the one exception —
 * it is the shared branded page, which inlines all of its own CSS and therefore
 * needs no panel theme to look right.
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
            // Same branded page as the admin console (App\Filament\Auth\BrandedLogin),
            // differing only by its seams: customer copy, no SSO, no link back to admin.
            ->login(\App\Filament\Portal\Auth\Login::class)
            // Self-hosted Vazirmatn for the panel body, replacing Filament's
            // bundled Inter (which has no Persian glyphs, so Persian text fell
            // back to whatever the OS offered).
            //
            // LocalFontProvider is passed EXPLICITLY: getFontProvider() defaults
            // to BunnyFontProvider the moment a custom family is set, so without
            // this the panel would silently fetch from fonts.bunny.net — which is
            // what the admin panel does today. Nothing here touches admin.
            //
            // The provider only emits a <link> to $url, so the @font-face blocks
            // live in public/fonts/vazirmatn/vazirmatn.css. Both URLs are Closures
            // so they resolve per request rather than being frozen at boot.
            //
            // Vazirmatn's Latin glyphs are clean, so this single family serves the
            // Persian and the English UI alike — no locale-conditional switching.
            ->font(
                'Vazirmatn',
                url: fn (): string => asset('fonts/vazirmatn/vazirmatn.css'),
                provider: LocalFontProvider::class,
                // Custom families get no preload by default (HasFont::getFontPreload).
                // 400 is the body weight, so preloading just that one buys the
                // first paint without pulling the other two up front.
                preload: fn (): array => [asset('fonts/vazirmatn/Vazirmatn-Regular.woff2')],
            )
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
