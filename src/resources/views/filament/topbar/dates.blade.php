{{-- Shamsi + Gregorian dates next to the global search, on one line.
     Rendered via the panels::global-search.before render hook. --}}
<div class="cs-topbar-dates">
    <span class="cs-td-num">{{ $jalali }}</span>
    <span class="cs-td-dot">·</span>
    <span class="cs-td-num">{{ $gregorian }}</span>
</div>

<style>
    .cs-topbar-dates {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
        /* Divider on the side facing the search (dates render before it). */
        padding-inline-end: 0.75rem;
        margin-inline-end: 0.25rem;
        border-inline-end: 1px solid var(--gray-200);
    }
    .dark .cs-topbar-dates {
        border-inline-end-color: var(--gray-800);
    }
    .cs-td-num {
        font-family: ui-monospace, monospace;
        font-size: 0.85rem; font-weight: 600; letter-spacing: 0.02em;
        color: #475569; font-variant-numeric: tabular-nums;
    }
    .dark .cs-td-num { color: #cbd5e1; }
    .cs-td-dot { color: #94a3b8; }

    @media (max-width: 768px) {
        .cs-topbar-dates { display: none; }
    }
</style>
