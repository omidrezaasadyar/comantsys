<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SubmitRequest extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedPlusCircle;
    protected static string | UnitEnum | null $navigationGroup = 'درخواست‌ها';
    protected static ?string $navigationLabel = 'ثبت درخواست';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.placeholder';

    public function getTitle(): string
    {
        return 'ثبت درخواست';
    }
}
