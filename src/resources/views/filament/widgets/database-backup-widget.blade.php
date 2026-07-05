{{-- Compact topbar form: single database icon → dropdown (backup / restore) + modals. --}}
<div class="fi-backup-tools">
    <x-filament-actions::group
        :actions="[$this->backupAction, $this->restoreAction]"
        :icon="\Filament\Support\Icons\Heroicon::CircleStack"
        icon-button
        color="gray"
        :label="__('dashboard.database')"
        :tooltip="__('dashboard.database')"
    />

    <x-filament-actions::modals />
</div>

<style>
    .fi-backup-tools {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
</style>
