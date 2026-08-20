{{-- Topbar tools cluster: settings link + theme toggle, next to global search.
     Rendered via the panels::global-search.after render hook.
     Backup moved into the Settings page (App\Filament\Pages\Settings). --}}
@php
    $showThemeSwitcher = filament()->hasDarkMode() && (! filament()->hasDarkModeForced());
    $canAccessSettings = \App\Filament\Pages\Settings::canAccess();
@endphp
<div class="fi-topbar-tools">
    @if ($canAccessSettings)
        <x-filament::icon-button
            tag="a"
            :href="route('filament.admin.pages.settings')"
            :icon="\Filament\Support\Icons\Heroicon::OutlinedCog6Tooth"
            icon-size="lg"
            color="gray"
            label="تنظیمات"
            tooltip="تنظیمات"
        />
    @endif
    @if ($showThemeSwitcher)
        <x-filament-panels::theme-switcher />
    @endif
</div>
<style>
    /* Vertical divider separating the cluster from the global search.
       Logical properties (inline-start) flip automatically for RTL/LTR. */
    .fi-topbar-tools {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-inline-start: 0.75rem;
        margin-inline-start: 0.25rem;
        border-inline-start: 1px solid var(--gray-200);
    }
    .dark .fi-topbar-tools {
        border-inline-start-color: var(--gray-800);
    }
    /* Phase 1: the topbar is the single theme control — hide the duplicate
       switcher that Filament renders inside the user-menu dropdown. */
    .fi-dropdown-list .fi-theme-switcher {
        display: none;
    }
</style>
