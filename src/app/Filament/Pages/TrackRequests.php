<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class TrackRequests extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;
    protected static string | UnitEnum | null $navigationGroup = 'درخواست‌ها';
    protected static ?string $navigationLabel = 'پیگیری درخواست‌ها';
    protected static ?int $navigationSort = 2;
    protected string $view = 'filament.pages.placeholder';

    public function getTitle(): string
    {
        return 'پیگیری درخواست‌ها';
    }
}
