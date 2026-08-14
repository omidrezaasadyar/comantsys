{{--
    resources/views/filament/records/show.blade.php

    Shared "Variant C" record-view layout: summary rail + flat detail
    sections + optional line-items table.

    Rendered from a ViewRecord page through App\Filament\Concerns\HasCustomRecordView,
    which normalises the data before it reaches here — so every key below is
    guaranteed to exist. Resources only supply their own field mapping.

    Data contract:
      $rail     => ['icon', 'image', 'title', 'subtitle', 'badge', 'metric', 'facts']
      $sections => [ ['heading', 'rows' => [ ['label','value','url','ltr','long'] ]] ]
      $items    => ['heading', 'columns' => [ ['label','align','ltr'] ],
                    'rows' => [ [ ['value','align','ltr'] ] ],
                    'totals' => [ ['label','value','ltr'] ]]  — or null
--}}

<div class="cs-rv">
    {{-- ─────────────── RAIL (right side under RTL) ─────────────── --}}
    <aside class="cs-rv-rail">
        <div class="cs-rv-avatar">
            @if (filled($rail['image']))
                <img src="{{ $rail['image'] }}" alt="{{ $rail['title'] }}" />
            @elseif (filled($rail['icon']))
                <x-filament::icon :icon="$rail['icon']" class="cs-rv-avatar-icon" />
            @endif
        </div>

        <h2 class="cs-rv-rail-title">{{ $rail['title'] }}</h2>

        @if (filled($rail['subtitle']))
            <p class="cs-rv-rail-subtitle">{{ $rail['subtitle'] }}</p>
        @endif

        @if (filled($rail['badge']))
            <div class="cs-rv-rail-badge">
                <x-filament::badge :color="$rail['badge']['color']">
                    {{ $rail['badge']['label'] }}
                </x-filament::badge>
            </div>
        @endif

        @if (filled($rail['metric']))
            <div class="cs-rv-metric">
                <span class="cs-rv-metric-label">{{ $rail['metric']['label'] }}</span>
                <span
                    class="cs-rv-metric-value"
                    @if ($rail['metric']['ltr']) dir="ltr" @endif
                >{{ $rail['metric']['value'] }}</span>
                @if (filled($rail['metric']['hint']))
                    <span class="cs-rv-metric-hint">{{ $rail['metric']['hint'] }}</span>
                @endif
            </div>
        @endif

        @if (filled($rail['facts']))
            <dl class="cs-rv-facts">
                @foreach ($rail['facts'] as $fact)
                    <div class="cs-rv-fact">
                        <dt class="cs-rv-fact-label">{{ $fact['label'] }}</dt>
                        <dd
                            @class(['cs-rv-fact-value', 'cs-rv-ltr' => $fact['ltr']])
                            @if ($fact['ltr']) dir="ltr" @endif
                        >
                            @if (filled($fact['url']))
                                <a href="{{ $fact['url'] }}" target="_blank" rel="noopener noreferrer">{{ $fact['value'] }}</a>
                            @else
                                {{ $fact['value'] }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </aside>

    {{-- ─────────────── MAIN (left side under RTL) ─────────────── --}}
    <div class="cs-rv-main">
        @foreach ($sections as $section)
            {{-- Detail sections are always ordinary grid blocks: a long field
                 inside one widens only its own row, never the whole section. --}}
            <section class="cs-rv-section">
                <h3 class="cs-rv-heading">{{ $section['heading'] }}</h3>

                <div class="cs-rv-rows">
                    @foreach ($section['rows'] as $row)
                        <div @class(['cs-rv-row', 'cs-rv-row-long' => $row['long']])>
                            <span class="cs-rv-label">{{ $row['label'] }}</span>
                            <span
                                @class(['cs-rv-value', 'cs-rv-ltr' => $row['ltr']])
                                @if ($row['ltr']) dir="ltr" @endif
                            >
                                @if (filled($row['url']))
                                    <a href="{{ $row['url'] }}" target="_blank" rel="noopener noreferrer">{{ $row['value'] }}</a>
                                @else
                                    {{ $row['value'] }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        {{-- Optional line-items region — always full width. --}}
        @if (filled($items))
            <section class="cs-rv-section cs-rv-section-full">
                @if (filled($items['heading']))
                    <h3 class="cs-rv-heading">{{ $items['heading'] }}</h3>
                @endif

                <div class="cs-rv-table-scroll">
                    <table class="cs-rv-table">
                        <thead>
                            <tr>
                                @foreach ($items['columns'] as $column)
                                    <th style="text-align: {{ $column['align'] }};">{{ $column['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items['rows'] as $row)
                                <tr>
                                    @foreach ($row as $cell)
                                        <td
                                            style="text-align: {{ $cell['align'] }};"
                                            @class(['cs-rv-ltr' => $cell['ltr']])
                                            @if ($cell['ltr']) dir="ltr" @endif
                                        >{{ $cell['value'] }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if (filled($items['totals']))
                    <div class="cs-rv-totals">
                        @foreach ($items['totals'] as $total)
                            <div class="cs-rv-total">
                                <span class="cs-rv-total-label">{{ $total['label'] }}</span>
                                <span
                                    @class(['cs-rv-total-value', 'cs-rv-ltr' => $total['ltr']])
                                    @if ($total['ltr']) dir="ltr" @endif
                                >{{ $total['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
</div>

<style>
    /* Colours come from the panel's own CSS custom properties (--gray-*,
       --primary-*) so light/dark follow the active Filament theme. */
    .cs-rv {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        gap: 1.5rem;
    }

    /* ── Rail ── */
    .cs-rv-rail {
        flex: 0 0 210px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.6rem;
        padding: 1.25rem 1rem;
        border-radius: 0.75rem;
        border: 1px solid var(--gray-200);
        background: var(--gray-50);
        text-align: center;
    }
    .dark .cs-rv-rail {
        border-color: var(--gray-800);
        background: color-mix(in oklab, var(--gray-900) 50%, transparent);
    }

    .cs-rv-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 4.5rem;
        height: 4.5rem;
        border-radius: 0.9rem;
        overflow: hidden;
        flex-shrink: 0;
        background: color-mix(in oklab, var(--primary-500) 12%, transparent);
        color: var(--primary-600);
    }
    .dark .cs-rv-avatar { color: var(--primary-400); }
    .cs-rv-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .cs-rv-avatar .cs-rv-avatar-icon { width: 2.25rem; height: 2.25rem; }

    .cs-rv-rail-title {
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.5;
        margin: 0;
        color: var(--gray-950);
        overflow-wrap: anywhere;
    }
    .dark .cs-rv-rail-title { color: var(--gray-50); }

    .cs-rv-rail-subtitle {
        font-size: 0.78rem;
        line-height: 1.6;
        margin: 0;
        color: var(--gray-500);
        overflow-wrap: anywhere;
    }
    .dark .cs-rv-rail-subtitle { color: var(--gray-400); }

    .cs-rv-rail-badge { margin-top: 0.15rem; }

    .cs-rv-metric {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        width: 100%;
        margin-top: 0.35rem;
        padding: 0.7rem 0.5rem;
        border-radius: 0.6rem;
        border: 1px solid var(--gray-200);
        background: color-mix(in oklab, var(--gray-100) 60%, transparent);
    }
    .dark .cs-rv-metric {
        border-color: var(--gray-800);
        background: color-mix(in oklab, var(--gray-950) 40%, transparent);
    }
    .cs-rv-metric-label { font-size: 0.72rem; color: var(--gray-500); }
    .dark .cs-rv-metric-label { color: var(--gray-400); }
    .cs-rv-metric-value {
        font-size: 1.15rem;
        font-weight: 800;
        line-height: 1.3;
        font-variant-numeric: tabular-nums;
        color: var(--gray-950);
    }
    .dark .cs-rv-metric-value { color: var(--gray-50); }
    .cs-rv-metric-hint { font-size: 0.7rem; color: var(--gray-500); }
    .dark .cs-rv-metric-hint { color: var(--gray-400); }

    .cs-rv-facts {
        width: 100%;
        margin: 0.35rem 0 0;
        display: flex;
        flex-direction: column;
    }
    .cs-rv-fact {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
        padding: 0.5rem 0;
        text-align: start;
    }
    .cs-rv-fact + .cs-rv-fact { border-top: 1px solid var(--gray-200); }
    .dark .cs-rv-fact + .cs-rv-fact { border-top-color: var(--gray-800); }
    .cs-rv-fact-label { font-size: 0.7rem; color: var(--gray-500); }
    .dark .cs-rv-fact-label { color: var(--gray-400); }
    .cs-rv-fact-value {
        margin: 0;
        font-size: 0.82rem;
        color: var(--gray-800);
        overflow-wrap: anywhere;
    }
    .dark .cs-rv-fact-value { color: var(--gray-200); }

    /* ── Main ── */
    /* Section blocks tile into as many columns as fit; a block never gets
       narrower than ~240px before the grid drops to a single column. */
    .cs-rv-main {
        flex: 1 1 420px;
        min-width: 0;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        align-items: start;
        align-content: start;
        gap: 1.75rem;
    }

    /* Reserved for the line-items table region, which is the only block that
       spans every column. Detail sections stay ordinary grid blocks so they
       pair two-per-row on wide screens. */
    .cs-rv-section-full { grid-column: 1 / -1; }
    .cs-rv-section { min-width: 0; }

    .cs-rv-heading {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0 0 0.35rem;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        color: var(--primary-600);
    }
    .dark .cs-rv-heading { color: var(--primary-400); }
    .cs-rv-heading::before {
        content: '';
        width: 3px;
        height: 0.95rem;
        border-radius: 2px;
        background: currentColor;
        flex-shrink: 0;
    }

    .cs-rv-rows { display: flex; flex-direction: column; }

    /* Label pinned to the start edge, value pushed to the opposite edge.
       `gap` is the hard floor, so the two can never touch even when the
       column is at its 240px minimum. */
    .cs-rv-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 1.25rem;
        padding: 0.6rem 0.25rem;
        font-size: 0.87rem;
    }
    .cs-rv-row + .cs-rv-row { border-top: 1px solid var(--gray-200); }
    .dark .cs-rv-row + .cs-rv-row { border-top-color: var(--gray-800); }

    .cs-rv-label {
        flex: 0 1 auto;
        min-width: 0;
        color: var(--gray-500);
    }
    .dark .cs-rv-label { color: var(--gray-400); }

    .cs-rv-value {
        flex: 0 1 auto;
        min-width: 0;
        text-align: end;
        color: var(--gray-900);
        overflow-wrap: anywhere;
        white-space: pre-line;
    }
    .dark .cs-rv-value { color: var(--gray-100); }
    .cs-rv-value a { color: var(--primary-600); text-decoration: underline; }
    .dark .cs-rv-value a { color: var(--primary-400); }
    .cs-rv-fact-value a { color: var(--primary-600); text-decoration: underline; }
    .dark .cs-rv-fact-value a { color: var(--primary-400); }

    /* Long free text: label above, value full width and start-aligned. */
    .cs-rv-row-long { flex-direction: column; align-items: stretch; gap: 0.3rem; }
    .cs-rv-row-long .cs-rv-label { flex: 0 0 auto; }
    .cs-rv-row-long .cs-rv-value { flex: 1 1 auto; text-align: start; line-height: 1.9; }

    /* Latin text / numbers must not be reordered by the RTL context.
       Alignment stays with the container so this works in rows, rail facts
       and table cells alike. */
    .cs-rv-ltr {
        unicode-bidi: isolate;
        font-variant-numeric: tabular-nums;
    }

    /* ── Optional items table ── */
    .cs-rv-table-scroll { overflow-x: auto; }
    .cs-rv-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }
    .cs-rv-table th {
        padding: 0.55rem 0.6rem;
        font-weight: 600;
        white-space: nowrap;
        color: var(--gray-500);
        border-bottom: 1px solid var(--gray-200);
    }
    .dark .cs-rv-table th {
        color: var(--gray-400);
        border-bottom-color: var(--gray-800);
    }
    .cs-rv-table td {
        padding: 0.55rem 0.6rem;
        color: var(--gray-900);
        border-bottom: 1px solid var(--gray-200);
    }
    .dark .cs-rv-table td {
        color: var(--gray-100);
        border-bottom-color: var(--gray-800);
    }
    .cs-rv-table tbody tr:last-child td { border-bottom: 0; }

    .cs-rv-totals {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        margin-top: 0.85rem;
        margin-inline-start: auto;
        width: min(20rem, 100%);
    }
    .cs-rv-total {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        font-size: 0.87rem;
    }
    .cs-rv-total-label { color: var(--gray-500); }
    .dark .cs-rv-total-label { color: var(--gray-400); }
    .cs-rv-total-value {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        color: var(--gray-950);
    }
    .dark .cs-rv-total-value { color: var(--gray-50); }

    @media (max-width: 768px) {
        .cs-rv-rail { flex: 1 1 100%; }
        .cs-rv-row { flex-direction: column; align-items: stretch; gap: 0.25rem; }
        .cs-rv-label { flex: 0 0 auto; }
        .cs-rv-value { text-align: start; }
    }
</style>
