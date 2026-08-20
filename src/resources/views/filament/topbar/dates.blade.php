{{-- Shamsi + Gregorian dates as a unified capsule, next to the global search.
     Rendered via the panels::global-search.before render hook.
     Data ($jalali, $gregorian) is supplied by the hook in AdminPanelProvider. --}}
<div class="cs-topbar-dates">
    <span class="cs-td-cal" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 5m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/>
            <path d="M16 3v4"/><path d="M8 3v4"/><path d="M4 11h16"/><path d="M11 15h1"/><path d="M12 15v3"/>
        </svg>
    </span>
    <span class="cs-td-seg">
        <span class="cs-td-label">شمسی</span>
        <span class="cs-td-num cs-td-num-primary">{{ $jalali }}</span>
    </span>
    <span class="cs-td-divider"></span>
    <span class="cs-td-seg">
        <span class="cs-td-label">میلادی</span>
        <span class="cs-td-num cs-td-num-secondary">{{ $gregorian }}</span>
    </span>
</div>
<style>
    .cs-topbar-dates {
        display: inline-flex;
        align-items: stretch;
        align-self: stretch;
        box-sizing: border-box;
        white-space: nowrap;
        background: #f6f7f9;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 9px;
        overflow: hidden;
        margin-inline-end: 0.5rem;
    }
    .dark .cs-topbar-dates {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.12);
    }
    .cs-td-cal {
        display: flex;
        align-items: center;
        padding-inline: 10px;
        color: #E8590C;
    }
    .dark .cs-td-cal { color: #F2843F; }
    .cs-td-seg {
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 0 12px;
    }
    .cs-td-label {
        font-size: 10px;
        color: #64748b;
    }
    .dark .cs-td-label { color: rgba(255, 255, 255, 0.4); }
    .cs-td-num {
        font-family: ui-monospace, monospace;
        font-size: 13px;
        letter-spacing: 0.02em;
        font-variant-numeric: tabular-nums;
    }
    .cs-td-num-primary { font-weight: 500; color: #334155; }
    .cs-td-num-secondary { color: #64748b; }
    .dark .cs-td-num-primary { color: #e2e8f0; }
    .dark .cs-td-num-secondary { color: #94a3b8; }
    .cs-td-divider {
        width: 1px;
        margin: 6px 0;
        background: rgba(0, 0, 0, 0.09);
    }
    .dark .cs-td-divider { background: rgba(255, 255, 255, 0.12); }
    @media (max-width: 768px) {
        .cs-topbar-dates { display: none; }
    }
</style>
