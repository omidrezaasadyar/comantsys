<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class Settings extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static ?string $navigationLabel = 'تنظیمات';
    protected static ?int $navigationSort = 99;
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.settings';

    public function getTitle(): string
    {
        return 'تنظیمات';
    }

    public function getHeader(): ?View
    {
        return view('filament.records.partials.header-band', [
            'header' => [
                'icon' => Heroicon::OutlinedCog6Tooth,
                'title' => 'تنظیمات',
                'subtitle' => 'مدیریت بخش‌های سیستمی برنامه: پشتیبان‌گیری، کاربران، دسترسی‌ها و نسخه.',
                'breadcrumbs' => [
                    ['label' => 'تنظیمات', 'url' => null],
                ],
            ],
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
