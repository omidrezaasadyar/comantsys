<x-filament-panels::page>
    <div class="cs-dash">
        @livewire(\App\Filament\Widgets\AccountWidget::class)
        @livewire(\App\Filament\Widgets\StatsOverviewWidget::class)
    </div>

    <style>
        .cs-dash {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
    </style>
</x-filament-panels::page>
