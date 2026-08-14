<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class PersonalPage extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'صفحه شخصی';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.personal-page';

    public string $activeTab = 'transactions';

    /**
     * @return array<string, string>
     */
    public function getTabs(): array
    {
        return [
            'transactions' => 'دریافت/پرداخت',
        ];
    }

    public function getTitle(): string
    {
        return 'صفحه شخصی';
    }

    public function getHeader(): ?View
    {
        return view('filament.records.partials.header-band', [
            'header' => [
                'icon' => \Filament\Support\Icons\Heroicon::OutlinedUserCircle,
                'title' => 'صفحه شخصی',
                'subtitle' => 'این صفحه حاوی مواردی است که صرفاً برای شما خواهد بود و هیچ کاربر دیگری اطلاعات آن را نخواهد دید.',
                'breadcrumbs' => [
                    ['label' => 'صفحه شخصی', 'url' => null],
                ],
            ],
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['partner', 'super_admin']) ?? false;
    }
}
