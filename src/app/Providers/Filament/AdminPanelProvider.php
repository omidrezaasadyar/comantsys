<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Enums\ThemeMode;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Morilog\Jalali\Jalalian;



class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(\App\Filament\Auth\Login::class)
            ->profile(\App\Filament\Auth\EditProfile::class)
            ->defaultAvatarProvider(\App\Filament\AvatarProviders\InitialsAvatarProvider::class)
            ->brandName('سامانه مدیریت شرکتی')
            ->sidebarCollapsibleOnDesktop()
            ->defaultThemeMode(ThemeMode::Light)
            ->colors([
                'primary' => Color::Sky,
                'gray' => [
                    // پله‌های روشن (تم روشن) — برای تغییر، فقط این اعداد را عوض کن
                    50  => '250, 250, 250',
                    100 => '244, 244, 245',
                    200 => '228, 228, 231',
                    300 => '212, 212, 216',
                    400 => '161, 161, 170',
                    500 => '113, 113, 122',
                    600 => '82, 82, 91',
                    700 => '63, 63, 70',
                    // پله‌های تیره (تم تیره) — سه سطح مجزا برای عمق
                    800 => '46, 46, 51',   // کارت‌ها — روشن‌ترین سطحِ تیره
                    900 => '35, 35, 40',   // سایدبار و نوار بالا
                    950 => '27, 27, 31',   // پس‌زمینهٔ صفحه — «تیرهٔ متوسط»، نه مشکی
                ],
            ])
            ->font('Vazirmatn')
            ->maxContentWidth(Width::Full)
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): View => view('filament.topbar.tools'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): View => view('filament.topbar.dates', [
                    'jalali'    => Jalalian::forge(now())->format('Y/m/d'),
                    'gregorian' => now()->format('Y/m/d'),
                ]),
            )
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
