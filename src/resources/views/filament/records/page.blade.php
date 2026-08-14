{{--
    resources/views/filament/records/page.blade.php

    Custom full-page record layout, assembled from reusable partials so other
    resources can later feed the same structure with their own mapping:

        header band  →  partials/header-band
        stat cards   →  partials/stat-cards
        body: side panel (relation managers) + detail cards
                     →  partials/relation-panel, partials/detail-card

    Rendered by App\Filament\Concerns\HasRecordPageLayout, which normalises
    $header / $stats / $panel / $cards before they get here.

    `$getChildSchema()` is the host View component's child schema — it holds the
    relation-manager Tabs component. This is the same accessor Filament's own
    grid.blade.php uses to print nested schema components.
--}}

<div class="cs-rp">
    @include('filament.records.partials.header-band', ['header' => $header])

    @include('filament.records.partials.stat-cards', ['stats' => $stats])

    @if (filled($rows))
        {{-- Opt-in body: explicit grid rows. A page uses this when its content
             lays out better side by side than as a full-width items table.
             The first block is the RIGHTMOST column under RTL. The width list
             is validated to bare fr tokens in the trait before it gets here. --}}
        @foreach ($rows as $row)
            <div class="cs-rp-colgrid" style="--cs-cols: {{ $row['template'] }};">
                @foreach ($row['blocks'] as $block)
                    @include('filament.records.partials.block', [
                        'block' => $block,
                        'relations' => $getChildSchema(),
                        'grow' => true,
                    ])
                @endforeach
            </div>
        @endforeach
    @else
        {{-- Default body: line items get the full page width on the heavy
             pages (Inquiry, Invoice) rather than being squeezed into the side
             column, then side panel + detail cards. --}}
        @if (filled($items))
            @include('filament.records.partials.items-table', ['table' => $items])
        @endif

        <div class="cs-rp-body">
            {{-- RIGHT column under RTL (1.5fr) --}}
            @include('filament.records.partials.relation-panel', [
                'panel' => $panel,
                'relations' => $getChildSchema(),
            ])

            {{-- LEFT column under RTL (1fr) --}}
            <div class="cs-rp-side">
                @foreach ($cards as $index => $card)
                    @include('filament.records.partials.detail-card', [
                        'card' => $card,
                        'grow' => $loop->last,
                    ])
                @endforeach
            </div>
        </div>
    @endif
</div>

<style>
    /*  THEME CONTRACT — this view owns no palette of its own.
     *
     *  Surfaces: every card carries Filament's own `fi-section` class, so its
     *  background, radius and ring come from the panel — including this
     *  project's overrides in resources/css/filament/admin/theme.css
     *  (.fi-section → #ffffff light / #1E2740 dark). Header strips carry
     *  `fi-section-header` for the same reason. Nothing is redeclared here.
     *
     *  Accent: var(--primary-*), which the panel emits from
     *  AdminPanelProvider's ->colors(['primary' => Color::Sky]).
     *  Muted text / dividers: var(--gray-*), emitted by the same mechanism.
     *  Status colours come from Filament's own badge component (the partials
     *  render it with :color="success" / "gray").
     *
     *  Change the panel's primary and this view follows automatically.
     */

    .cs-rp {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    /* ── Shared card shell ──
       Background / radius / ring intentionally omitted: `fi-section` supplies
       them, so these cards are the same surface as every other card in the
       panel. Only layout lives here. */
    .cs-rp-card { min-width: 0; }

    /* ── 2. Stat cards ── */
    .cs-rp-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
    .cs-rp-stat {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.85rem 0.95rem;
        min-width: 0;
    }
    .cs-rp-stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.1rem;
        height: 2.1rem;
        flex-shrink: 0;
        border-radius: 0.55rem;
        background: color-mix(in oklab, var(--primary-500) 12%, transparent);
        color: var(--primary-600);
    }
    .dark .cs-rp-stat-icon { color: var(--primary-400); }
    .cs-rp-stat-icon .fi-icon { width: 1.1rem; height: 1.1rem; }

    /*  Stat-card halo.
     *  The card keeps its neutral fi-section surface; only a tinted hairline
     *  and a soft drop shadow carry the hue, so the strip still reads as one
     *  row of cards rather than four coloured chips.
     *
     *  --cs-hue drives border + shadow + icon tint. Sky and green come from the
     *  panel tokens so they follow the theme; violet and amber are the two
     *  approved fixed accents (the panel registers no such colours).
     */
    .cs-rp-glow-sky    { --cs-hue: var(--primary-500); --cs-hue-icon: var(--primary-600); }
    .cs-rp-glow-green  { --cs-hue: var(--success-500); --cs-hue-icon: var(--success-600); }
    .cs-rp-glow-violet { --cs-hue: #a855f7;            --cs-hue-icon: #a855f7; }
    .cs-rp-glow-amber  { --cs-hue: #f59e0b;            --cs-hue-icon: #f59e0b; }

    .dark .cs-rp-glow-sky    { --cs-hue-icon: var(--primary-400); }
    .dark .cs-rp-glow-green  { --cs-hue-icon: var(--success-400); }
    .dark .cs-rp-glow-violet { --cs-hue-icon: #c084fc; }
    .dark .cs-rp-glow-amber  { --cs-hue-icon: #fbbf24; }

    .cs-rp-glow {
        border: 1px solid color-mix(in oklch, var(--cs-hue) 22%, var(--gray-200));
        box-shadow: 0 3px 14px -4px color-mix(in oklch, var(--cs-hue) 22%, transparent);
    }
    .dark .cs-rp-glow {
        border-color: color-mix(in oklch, var(--cs-hue) 22%, rgb(255 255 255 / 0.1));
    }
    .cs-rp-glow .cs-rp-stat-icon {
        background: color-mix(in oklab, var(--cs-hue) 12%, transparent);
        color: var(--cs-hue-icon);
    }
    .dark .cs-rp-glow .cs-rp-stat-icon { color: var(--cs-hue-icon); }

    .cs-rp-stat-body { display: flex; flex-direction: column; gap: 0.15rem; min-width: 0; }
    .cs-rp-stat-label {
        font-size: 0.7rem;
        line-height: 1.4;
        color: var(--gray-500);
    }
    .dark .cs-rp-stat-label { color: var(--gray-400); }
    /* Modest on purpose: same weight as body copy, one step up in size.
       text-align is pinned to `right` rather than left to the default `start`:
       the panel is RTL, so plain values (korea, busan) already land right, but
       an LTR-isolated value box (phone, website) resolves `start` to LEFT and
       would sit out of line. Pinning the box right aligns all four cards
       identically, while `unicode-bidi: isolate` on .cs-rp-ltr keeps the digit
       order correct inside it. */
    .cs-rp-stat-value {
        font-size: 0.85rem;
        font-weight: 500;
        line-height: 1.5;
        text-align: right;
        color: var(--gray-900);
        overflow-wrap: anywhere;
    }
    .dark .cs-rp-stat-value { color: var(--gray-100); }
    .cs-rp-stat-value a { color: var(--primary-600); text-decoration: underline; }
    .dark .cs-rp-stat-value a { color: var(--primary-400); }

    /* ── 3a. Opt-in body: explicit grid columns ──
       Every card stretches to the tallest in the row, so the row ends on one
       line. First child is the RIGHTMOST column under RTL.

       --cs-cols carries the per-page track list (validated fr tokens). Below
       the breakpoints the custom widths are dropped entirely and the columns
       reflow, so four narrow tracks stack instead of cramping. */
    .cs-rp-colgrid {
        display: grid;
        grid-template-columns: var(--cs-cols, repeat(auto-fit, minmax(260px, 1fr)));
        align-items: stretch;
        gap: 1rem;
    }
    .cs-rp-colgrid > * { min-width: 0; }

    @media (max-width: 1280px) {
        .cs-rp-colgrid { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
    }
    @media (max-width: 640px) {
        .cs-rp-colgrid { grid-template-columns: 1fr; }
    }

    /* ── 3b. Default body: side panel (right, 1.5fr) + detail cards (left, 1fr) ── */
    .cs-rp-body {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        align-items: stretch;
        gap: 1.25rem;
    }

    /* Right column can stack several blocks (relations / meta card / table).
       The relations block absorbs the slack so the column bottom stays level
       with the detail column next to it. */
    .cs-rp-panel-col {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        min-width: 0;
    }
    .cs-rp-panel { display: flex; flex-direction: column; min-width: 0; overflow: hidden; flex: 1 1 auto; }
    /* Strip fill, border and padding come from `fi-section-header`. */
    .cs-rp-panel-head {
        font-size: 0.82rem;
        font-weight: 700;
        text-align: start;
        color: var(--gray-700);
    }
    .dark .cs-rp-panel-head { color: var(--gray-200); }
    .cs-rp-panel-body { padding: 1rem; min-width: 0; flex: 1 1 auto; }

    /*  Opt-in: widen the relation manager's search field across its toolbar.
     *  Filament lays the toolbar out as
     *      .fi-ta-header-toolbar { justify-content: space-between; padding-inline: 1rem, 1.5rem @sm }
     *      > :nth-child(1) { flex-shrink: 0 }               (bulk/reorder actions)
     *      > :nth-child(2) { margin-inline-start: auto }    (search + filters)
     *  — container.css:44-57. It is that `ms-auto` alone that leaves the search
     *  field hanging in from the side; the search group takes the free space
     *  here instead.
     *
     *  The toolbar's own padding is deliberately NOT touched: it already equals
     *  the table's outer cell padding (cell.css:61 `first-of-type:ps-4
     *  sm:first-of-type:ps-6`), so the input lines up with the columns, and the
     *  gap keeps `.fi-input-wrp`'s rounded corner and its ring-1/ring-2 focus
     *  shadow (wrapper.css:2-5) clear of the clipping edge — this project makes
     *  `.fi-ta-ctn` `border-radius: .75rem; overflow: hidden` in dark mode
     *  (theme.css:80-84), which would otherwise shear the leading corner off.
     */
    .cs-rp-panel-flush .fi-ta-header-toolbar > :nth-child(2) {
        flex: 1 1 auto;
        margin-inline-start: 0;
        min-width: 0;
    }
    .cs-rp-panel-flush .fi-ta-header-toolbar > :nth-child(2) > * { flex: 1 1 auto; min-width: 0; }
    .cs-rp-panel-flush .fi-ta-search-field { width: 100%; }

    .cs-rp-side {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        min-width: 0;
    }
    /* Last card stretches so both columns end on the same line. */
    .cs-rp-grow { flex: 1 1 auto; }

    /* ── Detail cards ── */
    .cs-rp-detail { display: flex; flex-direction: column; overflow: hidden; }
    /* Strip fill, border and padding come from `fi-section-header`. */
    .cs-rp-detail-head {
        font-size: 0.8rem;
        font-weight: 700;
        text-align: start;
        color: var(--gray-700);
    }
    .dark .cs-rp-detail-head { color: var(--gray-200); }

    .cs-rp-rows { display: flex; flex-direction: column; padding: 0.25rem 1rem 0.5rem; }
    .cs-rp-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 1.25rem;
        padding: 0.65rem 0;
        font-size: 0.85rem;
    }
    /* Same divider Filament uses inside its own sections:
       `border-gray-200` light, `border-white/10` dark. The neutral overlay is
       what keeps the line correct on whatever colour the theme paints the card
       (this project sets a navy #1E2740 in dark mode). */
    .cs-rp-row + .cs-rp-row { border-top: 1px solid var(--gray-200); }
    .dark .cs-rp-row + .cs-rp-row { border-top-color: rgb(255 255 255 / 0.1); }

    .cs-rp-row-label { flex: 0 1 auto; min-width: 0; color: var(--gray-500); }
    .dark .cs-rp-row-label { color: var(--gray-400); }
    .cs-rp-row-value {
        flex: 0 1 auto;
        min-width: 0;
        text-align: end;
        color: var(--gray-900);
        overflow-wrap: anywhere;
        white-space: pre-line;
    }
    .dark .cs-rp-row-value { color: var(--gray-100); }
    .cs-rp-row-value a { color: var(--primary-600); text-decoration: underline; }
    .dark .cs-rp-row-value a { color: var(--primary-400); }

    /* ── Image block (company logo / stamp) ──
       The figure takes the card's remaining height and centres its content on
       both axes, so the image sits in the middle of the box however tall the
       cards beside it are. The image is bounded rather than stretched: a wide
       logo and a tall one both keep their aspect ratio. */
    .cs-rp-figure {
        flex: 1 1 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        min-height: 8rem;
        min-width: 0;
    }
    .cs-rp-figure-img {
        max-width: 100%;
        max-height: 12rem;
        object-fit: contain;
    }

    /* Missing file: the same muted treatment the header band's avatar uses,
       so an absent logo reads as "not uploaded" rather than as an error. */
    .cs-rp-figure-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.6rem;
        text-align: center;
        color: var(--gray-400);
    }
    .dark .cs-rp-figure-empty { color: var(--gray-500); }
    .cs-rp-figure-icon { width: 3rem; height: 3rem; }
    .cs-rp-figure-note {
        margin: 0;
        font-size: 0.78rem;
        line-height: 1.7;
        color: var(--gray-500);
    }
    .dark .cs-rp-figure-note { color: var(--gray-400); }

    /* Two-column label/value grid inside a card. The default divider rule
       (`.cs-rp-row + .cs-rp-row`) would draw a line above the top-RIGHT cell
       but not the top-LEFT one, so it is replaced by a per-grid-row rule:
       from the third child on, i.e. every cell below the first line. */
    .cs-rp-rows-2col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        column-gap: 1.75rem;
    }
    .cs-rp-rows-2col .cs-rp-row + .cs-rp-row { border-top: 0; }
    .cs-rp-rows-2col .cs-rp-row:nth-child(n + 3) { border-top: 1px solid var(--gray-200); }
    .dark .cs-rp-rows-2col .cs-rp-row:nth-child(n + 3) { border-top-color: rgb(255 255 255 / 0.1); }

    @media (max-width: 900px) {
        /* Back to one column — and back to the plain sibling divider, since
           every row is now its own line. */
        .cs-rp-rows-2col { grid-template-columns: 1fr; }
        .cs-rp-rows-2col .cs-rp-row:nth-child(n + 3) { border-top: 0; }
        .cs-rp-rows-2col .cs-rp-row + .cs-rp-row { border-top: 1px solid var(--gray-200); }
        .dark .cs-rp-rows-2col .cs-rp-row + .cs-rp-row { border-top-color: rgb(255 255 255 / 0.1); }
    }

    /* Long free text: label above, value full width. */
    .cs-rp-row-long { flex-direction: column; align-items: stretch; gap: 0.3rem; }
    .cs-rp-row-long .cs-rp-row-value { text-align: start; line-height: 1.9; }

    /* Inline single-field row: «label :» then the value on the SAME line, the
       value taking all remaining width and wrapping inside the card instead of
       being pushed to the opposite edge by the default space-between. */
    .cs-rp-row-inline { justify-content: flex-start; gap: 0.5rem; }
    .cs-rp-row-inline .cs-rp-row-label { flex: 0 0 auto; }
    .cs-rp-row-inline .cs-rp-row-value {
        flex: 1 1 auto;
        text-align: start;
        line-height: 1.9;
    }

    /* Latin text / numbers must not be reordered by the RTL context. */
    .cs-rp-ltr {
        unicode-bidi: isolate;
        font-variant-numeric: tabular-nums;
    }

    /* ── Items table (full width, or bare inside a panel block) ── */
    .cs-rp-items { display: flex; flex-direction: column; overflow: hidden; }
    .cs-rp-table-region { padding: 0.75rem 1rem 1rem; min-width: 0; }
    .cs-rp-table-scroll { overflow-x: auto; }
    .cs-rp-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }
    .cs-rp-table th {
        padding: 0.55rem 0.6rem;
        font-weight: 600;
        white-space: nowrap;
        color: var(--gray-500);
        border-bottom: 1px solid var(--gray-200);
    }
    .dark .cs-rp-table th {
        color: var(--gray-400);
        border-bottom-color: rgb(255 255 255 / 0.1);
    }
    .cs-rp-table td {
        padding: 0.55rem 0.6rem;
        color: var(--gray-900);
        border-bottom: 1px solid var(--gray-200);
    }
    .dark .cs-rp-table td {
        color: var(--gray-100);
        border-bottom-color: rgb(255 255 255 / 0.1);
    }
    .cs-rp-table tbody tr:last-child td { border-bottom: 0; }

    /* A column given an explicit width keeps its figures on one line, so large
       amounts sit squarely under their heading instead of wrapping. */
    .cs-rp-table th.cs-rp-fixed,
    .cs-rp-table td.cs-rp-fixed { white-space: nowrap; }

    /* Opt-in fixed amount column. The last column gets a set width and the
       normaliser has already pinned its header + cells to `text-align: left`,
       so the digits sit directly under the label instead of drifting apart
       (an RTL <th> and an LTR-isolated <td> resolve `end` to opposite edges).
       The first column absorbs the remaining width. */
    .cs-rp-amount-col .cs-rp-table th:last-child,
    .cs-rp-amount-col .cs-rp-table td:last-child {
        width: var(--cs-amount-w);
        min-width: var(--cs-amount-w);
    }
    .cs-rp-amount-col .cs-rp-table th:first-child,
    .cs-rp-amount-col .cs-rp-table td:first-child { width: auto; }

    /* Totals share the same column, so they line up with the amounts above. */
    .cs-rp-amount-col .cs-rp-totals {
        width: 100%;
        margin-inline-start: 0;
    }
    .cs-rp-amount-col .cs-rp-total-value {
        flex: 0 0 auto;
        width: var(--cs-amount-w);
        text-align: left;
        padding-inline-end: 0.6rem;
    }
    .cs-rp-amount-col .cs-rp-total-label { padding-inline-start: 0.6rem; }

    .cs-rp-empty {
        margin: 0;
        padding: 0.75rem 0.25rem;
        font-size: 0.83rem;
        color: var(--gray-500);
    }
    .dark .cs-rp-empty { color: var(--gray-400); }

    .cs-rp-totals {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        margin-top: 0.9rem;
        padding-top: 0.75rem;
        margin-inline-start: auto;
        width: min(22rem, 100%);
        border-top: 1px solid var(--gray-200);
    }
    .dark .cs-rp-totals { border-top-color: rgb(255 255 255 / 0.1); }
    .cs-rp-total {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        font-size: 0.86rem;
    }
    .cs-rp-total-label { color: var(--gray-500); }
    .dark .cs-rp-total-label { color: var(--gray-400); }
    .cs-rp-total-value {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        color: var(--gray-950);
    }
    .dark .cs-rp-total-value { color: var(--gray-50); }
    /* A loss reads in the panel's own danger colour, same token the tables and
       badges use for negative states. */
    .cs-rp-total-danger { color: var(--danger-600); }
    .dark .cs-rp-total-danger { color: var(--danger-400); }

    @media (max-width: 1024px) {
        .cs-rp-body { grid-template-columns: 1fr; }
    }

    @media (max-width: 640px) {
        .cs-rp-row { flex-direction: column; align-items: stretch; gap: 0.25rem; }
        .cs-rp-row-value { text-align: start; }
        /* An inline row keeps its label and value on one line at every width —
           that is the whole point of the variant; the value just wraps under
           itself when it runs out of room. */
        .cs-rp-row-inline { flex-direction: row; align-items: baseline; gap: 0.5rem; }
    }
</style>
