<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class IncomingLetters extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedArrowDownTray;
    protected static string | UnitEnum | null $navigationGroup = 'امور اداری';
    protected static ?string $navigationLabel = 'نامه‌های وارده';
    protected static ?int $navigationSort = 2;
    protected string $view = 'filament.pages.placeholder';

    public function getTitle(): string
    {
        return 'نامه‌های وارده';
    }
}
