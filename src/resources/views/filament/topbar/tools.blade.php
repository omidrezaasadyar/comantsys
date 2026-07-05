{{-- Topbar tools cluster: DB backup + theme toggle, next to global search.
     Rendered via the panels::global-search.after render hook. --}}
@php
    $showThemeSwitcher = filament()->hasDarkMode() && (! filament()->hasDarkModeForced());
@endphp

<div class="fi-topbar-tools">
    @livewire(\App\Filament\Widgets\DatabaseBackupWidget::class)

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
