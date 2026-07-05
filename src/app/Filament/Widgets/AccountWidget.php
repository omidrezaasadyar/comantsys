<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class AccountWidget extends Widget
{
    protected string $view = 'filament.widgets.account-widget';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return Filament::auth()->check();
    }
}
