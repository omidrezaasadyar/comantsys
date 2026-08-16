{{--
    resources/views/filament/portal/request-view.blade.php

    The customer's read-only view of one request. Rendered by
    App\Filament\Portal\Resources\PortalRequests\Pages\ViewPortalRequest, which
    normalises everything below before it gets here:

        halo     → status box (tone + icon + title + subtitle + status pill)
        boxes    → eight icon-chip cards; the two status ones carry a tone
        response → staff's official response, ONLY when non-empty
        details  → subject, description, and the customer's own attachments

    ── Why every colour below is a literal hex ──
    The first cut of this page styled itself with `rgb(var(--danger-50))` and
    friends. That was wrong twice over: Filament v5 uses those tokens as WHOLE
    colour values (`color: var(--danger-500)`, per the compiled theme), never as
    the bare RGB triplets `rgb()` expects, and the 50/950 shades are not defined
    in any shipped stylesheet at all. Every such declaration was therefore
    invalid and dropped by the browser, which is exactly why the page rendered
    flat. Tailwind utilities are no escape either — Tailwind v4 compiles only
    what its source scan finds, and a class that lives only in this blade is not
    guaranteed to survive that scan (CLAUDE.md §6).

    So: plain CSS, literal hex, and one small palette of `--prv-*` custom
    properties declared on `.prv` itself. Nothing here depends on the panel
    theme, so nothing here can silently go colourless again.

    A tone class (`prv-t-danger` …) sets only the three `--prv-tone-*` slots;
    the halo, the pills and the tinted status boxes all read those slots, so one
    class recolours a whole component and the tones stay consistent between them.

    Nothing here sanitises: tones are whitelisted in the page (TONES), URLs come
    from route()/getUrl(), and every value prints through {{ }}. No raw echo.
--}}

<div class="prv">

    {{-- ── back link ── --}}
    <a href="{{ $back['url'] }}" class="prv-back">
        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedArrowRight" />
        <span>{{ $back['label'] }}</span>
    </a>

    {{-- ── a) status halo, coloured by viewState() ── --}}
    <div @class(['prv-halo', 'prv-t-' . $halo['tone'] => filled($halo['tone'])])>
        <span class="prv-halo-icon">
            <x-filament::icon :icon="$halo['icon']" />
        </span>

        <div class="prv-halo-body">
            <div class="prv-halo-head">
                <h2 class="prv-halo-title">{{ $halo['title'] }}</h2>
                <span class="prv-pill">{{ $halo['pill'] }}</span>
            </div>

            @if (filled($halo['subtitle']))
                <p class="prv-halo-sub">{{ $halo['subtitle'] }}</p>
            @endif
        </div>
    </div>

    {{-- ── b) eight icon-chip info boxes: six neutral, two tinted by status ── --}}
    <div class="prv-boxes">
        @foreach ($boxes as $box)
            <div @class([
                'prv-box',
                'prv-tinted' => filled($box['tone']),
                'prv-t-' . $box['tone'] => filled($box['tone']),
            ])>
                <span class="prv-box-icon">
                    <x-filament::icon :icon="$box['icon']" />
                </span>

                <div class="prv-box-body">
                    <span class="prv-box-label">{{ $box['label'] }}</span>
                    <span
                        @class(['prv-box-value', 'prv-ltr' => $box['ltr']])
                        @if ($box['ltr']) dir="ltr" @endif
                    >{{ $box['value'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── c) official response — only when staff wrote one ── --}}
    @if (filled($response))
        <div class="prv-card">
            <div class="prv-card-head prv-head-accent">
                <span class="prv-card-icon">
                    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedChatBubbleBottomCenterText" />
                </span>
                <h3 class="prv-card-title">{{ $response['heading'] }}</h3>
            </div>

            {{-- dir="auto" so a Persian answer reads RTL and a Latin one LTR,
                 decided per block by the browser rather than hardcoded. --}}
            <div class="prv-card-body">
                <p class="prv-prose" dir="auto">{{ $response['body'] }}</p>
            </div>
        </div>
    @endif

    {{-- ── d) the request itself, read-only ── --}}
    <div class="prv-card">
        <div class="prv-card-head">
            <span class="prv-card-icon">
                <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedDocumentText" />
            </span>
            <h3 class="prv-card-title">{{ $details['heading'] }}</h3>
        </div>

        <div class="prv-card-body">
            <div class="prv-field">
                <span class="prv-field-label">{{ $details['subject_label'] }}</span>
                <span class="prv-field-value" dir="auto">{{ $details['subject'] }}</span>
            </div>

            <div class="prv-field">
                <span class="prv-field-label">{{ $details['description_label'] }}</span>
                <p class="prv-field-value prv-prose" dir="auto">{{ $details['description'] }}</p>
            </div>

            <div class="prv-field">
                <span class="prv-field-label">{{ $details['attachments_label'] }}</span>

                @if (filled($details['attachments']))
                    <ul class="prv-files">
                        @foreach ($details['attachments'] as $file)
                            <li>
                                <a href="{{ $file['url'] }}" target="_blank" rel="noopener noreferrer" class="prv-file">
                                    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedPaperClip" />
                                    <span>{{ $file['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <span class="prv-field-value prv-muted">{{ $details['attachments_empty'] }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    /* ── palette ─────────────────────────────────────────────────────────
       Literal hex only. `--prv-tone-*` is the slot a tone class fills; every
       tinted component reads the slot, never a colour directly.            */
    .prv {
        --prv-surface: #ffffff;
        --prv-line: #e5e7eb;
        --prv-label: #6b7280;
        --prv-value: #111827;
        --prv-title: #111827;

        --prv-accent: #2563eb;
        --prv-accent-tint: #eff6ff;
        --prv-accent-line: #bfdbfe;

        --prv-tone-tint: #f9fafb;
        --prv-tone-line: #e5e7eb;
        --prv-tone-fg: #374151;

        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .prv-t-danger  { --prv-tone-tint: #fef2f2; --prv-tone-line: #fca5a5; --prv-tone-fg: #b91c1c; }
    .prv-t-warning { --prv-tone-tint: #fffbeb; --prv-tone-line: #fcd34d; --prv-tone-fg: #b45309; }
    .prv-t-success { --prv-tone-tint: #f0fdf4; --prv-tone-line: #86efac; --prv-tone-fg: #15803d; }
    .prv-t-info    { --prv-tone-tint: #eff6ff; --prv-tone-line: #93c5fd; --prv-tone-fg: #1d4ed8; }
    /* Neutral tone (request_status 'received'). Deliberately a step darker
       than the 50-shade the coloured tones use: they read as tinted because of
       HUE, so a 2% luminance lift is enough for them. Grey has no hue to carry
       the signal, and #f9fafb on a #ffffff surface is invisible — the box then
       looks identical to the six untinted ones, i.e. accidental. #f3f4f6 gives
       it the same presence as the success/warning tints without adding colour. */
    .prv-t-gray    { --prv-tone-tint: #f3f4f6; --prv-tone-line: #d1d5db; --prv-tone-fg: #374151; }

    /* ── back link ── */
    .prv-back {
        display: inline-flex; align-items: center; gap: .375rem;
        align-self: flex-start;
        font-size: .8125rem; color: var(--prv-label); text-decoration: none;
    }
    .prv-back:hover { color: var(--prv-accent); }
    .prv-back svg { width: 1rem; height: 1rem; }

    /* ── a) halo ── */
    .prv-halo {
        display: flex; gap: .875rem; align-items: flex-start;
        padding: 1.125rem 1.25rem;
        border: 1.5px solid var(--prv-tone-line);
        border-radius: .875rem;
        background-color: var(--prv-tone-tint);
    }
    .prv-halo-icon {
        flex: 0 0 auto;
        display: inline-flex; align-items: center; justify-content: center;
        width: 2.75rem; height: 2.75rem;
        border-radius: 9999px;
        background-color: #ffffff;
        border: 1.5px solid var(--prv-tone-line);
        color: var(--prv-tone-fg);
    }
    .prv-halo-icon svg { width: 1.5rem; height: 1.5rem; }
    .prv-halo-body { flex: 1 1 auto; min-width: 0; }
    .prv-halo-head {
        display: flex; flex-wrap: wrap; align-items: center;
        gap: .5rem; justify-content: space-between;
    }
    .prv-halo-title { margin: 0; font-size: 1.0625rem; font-weight: 700; color: var(--prv-tone-fg); }
    .prv-halo-sub { margin: .375rem 0 0; font-size: .8125rem; line-height: 1.7; color: var(--prv-tone-fg); opacity: .85; }

    /* Pill inherits the halo's tone slots, so it always matches its box. */
    .prv-pill {
        display: inline-flex; align-items: center;
        padding: .1875rem .625rem; border-radius: 9999px;
        font-size: .6875rem; font-weight: 600; white-space: nowrap;
        background-color: #ffffff;
        border: 1px solid var(--prv-tone-line);
        color: var(--prv-tone-fg);
    }

    /* ── b) info boxes ── */
    .prv-boxes { display: grid; gap: .75rem; grid-template-columns: repeat(4, minmax(0, 1fr)); }

    .prv-box {
        display: flex; align-items: center; gap: .625rem;
        padding: .8125rem .875rem;
        border: 1px solid var(--prv-line);
        border-radius: .75rem;
        background-color: var(--prv-surface);
    }
    .prv-box-icon {
        flex: 0 0 auto;
        display: inline-flex; align-items: center; justify-content: center;
        width: 2.125rem; height: 2.125rem; border-radius: .625rem;
        background-color: var(--prv-accent-tint);
        color: var(--prv-accent);
    }
    .prv-box-icon svg { width: 1.125rem; height: 1.125rem; }
    .prv-box-body { display: flex; flex-direction: column; gap: .1875rem; min-width: 0; }
    .prv-box-label { font-size: .6875rem; color: var(--prv-label); }
    .prv-box-value { font-size: .8125rem; font-weight: 700; color: var(--prv-value); word-break: break-word; }
    .prv-ltr { direction: ltr; text-align: start; font-variant-numeric: tabular-nums; }

    /* The two status boxes: tinted to their own state. */
    .prv-box.prv-tinted {
        background-color: var(--prv-tone-tint);
        border-color: var(--prv-tone-line);
    }
    .prv-tinted .prv-box-icon { background-color: #ffffff; color: var(--prv-tone-fg); }
    .prv-tinted .prv-box-value { color: var(--prv-tone-fg); }

    /* ── c/d) cards ── */
    .prv-card {
        border: 1px solid var(--prv-line);
        border-radius: .875rem;
        background-color: var(--prv-surface);
        overflow: hidden;
    }
    .prv-card-head {
        display: flex; align-items: center; gap: .5rem;
        padding: .75rem 1.125rem;
        border-bottom: 1px solid var(--prv-line);
    }
    .prv-head-accent {
        background-color: var(--prv-accent-tint);
        border-bottom-color: var(--prv-accent-line);
    }
    .prv-card-icon { display: inline-flex; color: var(--prv-accent); }
    .prv-card-icon svg { width: 1.125rem; height: 1.125rem; }
    .prv-card-title { margin: 0; font-size: .875rem; font-weight: 700; color: var(--prv-title); }
    .prv-card-body { display: flex; flex-direction: column; gap: 1rem; padding: 1.125rem; }

    .prv-field { display: flex; flex-direction: column; gap: .25rem; }
    .prv-field-label { font-size: .6875rem; color: var(--prv-label); }
    .prv-field-value { margin: 0; font-size: .875rem; line-height: 1.8; color: var(--prv-value); word-break: break-word; }
    .prv-muted { color: var(--prv-label); }
    /* Keeps the customer's own line breaks without ever emitting raw markup. */
    .prv-prose { white-space: pre-wrap; }

    /* ── attachments ── */
    .prv-files { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .375rem; }
    .prv-file {
        display: inline-flex; align-items: center; gap: .375rem;
        font-size: .8125rem; color: var(--prv-accent); text-decoration: none;
    }
    .prv-file:hover { text-decoration: underline; }
    .prv-file svg { width: 1rem; height: 1rem; }

    /* ── dark mode ── Filament toggles a `.dark` class on an ancestor. ── */
    .dark .prv {
        --prv-surface: #18181b;
        --prv-line: #3f3f46;
        --prv-label: #a1a1aa;
        --prv-value: #f4f4f5;
        --prv-title: #f4f4f5;

        --prv-accent: #60a5fa;
        --prv-accent-tint: #1e293b;
        --prv-accent-line: #334155;

        --prv-tone-tint: #27272a;
        --prv-tone-line: #3f3f46;
        --prv-tone-fg: #e4e4e7;
    }

    .dark .prv-t-danger  { --prv-tone-tint: #2a1416; --prv-tone-line: #b91c1c; --prv-tone-fg: #fca5a5; }
    .dark .prv-t-warning { --prv-tone-tint: #2a1f0e; --prv-tone-line: #b45309; --prv-tone-fg: #fcd34d; }
    .dark .prv-t-success { --prv-tone-tint: #10261a; --prv-tone-line: #15803d; --prv-tone-fg: #86efac; }
    .dark .prv-t-info    { --prv-tone-tint: #131f33; --prv-tone-line: #1d4ed8; --prv-tone-fg: #93c5fd; }
    /* Same reasoning inverted: lift it clear of the #18181b dark surface. */
    .dark .prv-t-gray    { --prv-tone-tint: #2b2b31; --prv-tone-line: #52525b; --prv-tone-fg: #d4d4d8; }

    /* On dark, the "white circle" reads as the surface colour instead. */
    .dark .prv-halo-icon,
    .dark .prv-pill,
    .dark .prv-tinted .prv-box-icon { background-color: #18181b; }

    /* ── responsive ── */
    @media (max-width: 1024px) { .prv-boxes { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 640px)  { .prv-boxes { grid-template-columns: minmax(0, 1fr); } }
</style>
