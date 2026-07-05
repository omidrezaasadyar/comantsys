<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WorldClocksWidget extends Widget
{
    protected string $view = 'filament.widgets.world-clocks-widget';

    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';
}
