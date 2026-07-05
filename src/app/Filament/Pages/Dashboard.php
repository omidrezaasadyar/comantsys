<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DatabaseBackupWidget;
use App\Filament\Widgets\DateWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\WorldClocksWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    public function getColumns(): int | array
    {
        return 3;
    }

    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            DateWidget::class,
            DatabaseBackupWidget::class,
            WorldClocksWidget::class,
            StatsOverviewWidget::class,
        ];
    }
}
