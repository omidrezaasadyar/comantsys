<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">{{ __('dashboard.backup_db') }}</x-slot>

        <div style="display: flex; align-items: center; justify-content: center; gap: 1.5rem; padding: 0.5rem 0;">
            {{ $this->backupAction }}
            {{ $this->restoreAction }}
        </div>

        <x-filament-actions::modals />
    </x-filament::section>
</x-filament-widgets::widget>
