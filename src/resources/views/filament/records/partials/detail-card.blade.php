{{--
    resources/views/filament/records/partials/detail-card.blade.php

    Reusable detail card. Expects $card:
      ['heading', 'rows' => [ ['label','value','ltr','long','inline','url','badge'], … ]]

    A row carrying 'badge' renders its value as a coloured pill instead of
    plain text — the layout's stand-in for the infolist's TextEntry->badge().

    A blank 'heading' drops the header strip entirely, for a single-field card
    that names itself inline ('inline' row) rather than in a strip above.

    Optional $grow — the last card in a column sets it so its bottom edge lines
    up with the side panel next to it (equal-height columns).
--}}

@php($grow = $grow ?? false)

<section @class(['fi-section', 'cs-rp-card', 'cs-rp-detail', 'cs-rp-grow' => $grow])>
    @if (filled($card['heading']))
        <header class="fi-section-header cs-rp-detail-head">{{ $card['heading'] }}</header>
    @endif

    {{-- 'columns' => 2 turns the row list into a two-column label/value grid,
         so a short field list does not leave a wide card mostly empty. It
         collapses back to one column on narrow viewports. --}}
    <div @class(['cs-rp-rows', 'cs-rp-rows-2col' => ($card['columns'] ?? 1) === 2])>
        @foreach ($card['rows'] as $row)
            <div @class([
                'cs-rp-row',
                'cs-rp-row-long' => $row['long'],
                'cs-rp-row-inline' => $row['inline'] ?? false,
            ])>
                {{-- A card whose header strip already names the single field it
                     holds passes no label, so the name is not printed twice. --}}
                @if (filled($row['label']))
                    <span class="cs-rp-row-label">{{ $row['label'] }}</span>
                @endif
                <span
                    @class(['cs-rp-row-value', 'cs-rp-ltr' => $row['ltr']])
                    @if ($row['ltr']) dir="ltr" @endif
                >
                    @if (filled($row['badge'] ?? null))
                        {{-- Same component and the same colour vocabulary the
                             header band's badge uses, so a status pill looks
                             identical wherever it appears. --}}
                        <x-filament::badge :color="$row['badge']">{{ $row['value'] }}</x-filament::badge>
                    @elseif (filled($row['url']))
                        <a href="{{ $row['url'] }}" target="_blank" rel="noopener noreferrer">{{ $row['value'] }}</a>
                    @else
                        {{ $row['value'] }}
                    @endif
                </span>
            </div>
        @endforeach
    </div>
</section>
