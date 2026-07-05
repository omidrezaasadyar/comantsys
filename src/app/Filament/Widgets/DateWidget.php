<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Morilog\Jalali\Jalalian;

class DateWidget extends Widget
{
    protected string $view = 'filament.widgets.date-widget';

    protected static ?int $sort = -2;

    protected int | string | array $columnSpan = 1;

    public function getViewData(): array
    {
        $now = now();

        return [
            'gregorian'    => $now->format('Y/m/d'),
            'gregorianDay' => $now->format('l'),
            'jalali'       => Jalalian::forge($now)->format('Y/m/d'),
            'jalaliDay'    => Jalalian::forge($now)->format('l'),
        ];
    }
}
