{{--
    resources/views/filament/records/partials/block.blade.php

    Renders ONE content block. Shared by the side panel and by the two-column
    body rows, so both places build the same card shells.

    $block     ['type' => 'relations' | 'card' | 'table' | 'image', …]
    $relations the page's relation-manager component (only used by 'relations')
    $grow      stretch the card to fill its column (default false)
    $heading   optional heading override for the 'relations' block
--}}

@php
    $grow = $grow ?? false;
    $heading = $heading ?? ($block['heading'] ?? null);
@endphp

@if ($block['type'] === 'relations')
    <div @class(['fi-section', 'cs-rp-card', 'cs-rp-panel', 'cs-rp-grow' => $grow])>
        @if (filled($heading))
            <header class="fi-section-header cs-rp-panel-head">{{ $heading }}</header>
        @endif

        {{-- `flush` lets the relation manager's own toolbar run to the panel
             body's edges, so its search field lines up with the table columns
             underneath instead of floating in from the side. --}}
        <div @class(['cs-rp-panel-body', 'cs-rp-panel-flush' => $block['flush'] ?? false])>
            {{ $relations }}
        </div>
    </div>
@elseif ($block['type'] === 'card')
    @include('filament.records.partials.detail-card', [
        'card' => $block['card'],
        'grow' => $grow,
    ])
@elseif ($block['type'] === 'image')
    <section @class(['fi-section', 'cs-rp-card', 'cs-rp-detail', 'cs-rp-grow' => $grow])>
        @if (filled($heading))
            <header class="fi-section-header cs-rp-detail-head">{{ $heading }}</header>
        @endif

        {{-- The figure absorbs the card's slack, so the image stays centred no
             matter how tall the boxes beside it make the row. --}}
        <div class="cs-rp-figure">
            @if (filled($block['image']['url']))
                <img
                    src="{{ $block['image']['url'] }}"
                    alt="{{ $block['image']['alt'] }}"
                    class="cs-rp-figure-img"
                />
            @else
                <div class="cs-rp-figure-empty">
                    @if (filled($block['image']['icon']))
                        <x-filament::icon :icon="$block['image']['icon']" class="cs-rp-figure-icon" />
                    @endif

                    @if (filled($block['image']['empty']))
                        <p class="cs-rp-figure-note">{{ $block['image']['empty'] }}</p>
                    @endif
                </div>
            @endif
        </div>
    </section>
@elseif ($block['type'] === 'table')
    <section @class(['fi-section', 'cs-rp-card', 'cs-rp-detail', 'cs-rp-grow' => $grow])>
        @if (filled($block['table']['heading']))
            <header class="fi-section-header cs-rp-detail-head">{{ $block['table']['heading'] }}</header>
        @endif

        @include('filament.records.partials.items-table', [
            'table' => $block['table'],
            'bare' => true,
        ])
    </section>
@endif
