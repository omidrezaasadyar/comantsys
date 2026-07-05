<x-filament-widgets::widget>
    <x-filament::section>
        <div class="cs-date">
            <div class="cs-date-row">
                <span class="cs-date-val">{{ $jalaliDay }}</span>
                <span class="cs-date-num">{{ $jalali }}</span>
            </div>
            <div class="cs-date-sep"></div>
            <div class="cs-date-row">
                <span class="cs-date-val">{{ $gregorianDay }}</span>
                <span class="cs-date-num">{{ $gregorian }}</span>
            </div>
        </div>

        <style>
            .cs-date { display: flex; flex-direction: column; gap: 0.55rem; padding: 0.25rem 0; }
            .cs-date-row { display: flex; align-items: baseline; justify-content: center; gap: 0.55rem; }
            .cs-date-val { font-size: 0.9rem; color: #64748b; }
            .dark .cs-date-val { color: #94a3b8; }
            .cs-date-num {
                font-family: ui-monospace, monospace;
                font-size: 1.15rem; font-weight: 700; letter-spacing: 0.02em;
                color: #0f172a; font-variant-numeric: tabular-nums;
            }
            .dark .cs-date-num { color: #f1f5f9; }
            .cs-date-sep {
                height: 1px;
                background: linear-gradient(to right, transparent, rgba(148,163,184,0.3), transparent);
            }
        </style>
    </x-filament::section>
</x-filament-widgets::widget>
