<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class OutgoingLetters extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedArrowUpTray;
    protected static string | UnitEnum | null $navigationGroup = 'امور اداری';
    protected static ?string $navigationLabel = 'نامه‌های صادره';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.placeholder';

    public function getTitle(): string
    {
        return 'نامه‌های صادره';
    }
}
