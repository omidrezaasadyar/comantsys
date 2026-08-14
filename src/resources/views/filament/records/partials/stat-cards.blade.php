{{--
    resources/views/filament/records/partials/stat-cards.blade.php

    Reusable stat-card row. Expects $stats:
      [ ['icon', 'label', 'value', 'ltr', 'url', 'glow'], … ]

    `glow` is a whitelisted key ('sky' | 'green' | 'violet' | 'amber' | null)
    chosen by what the number MEANS, not by which resource it came from, so the
    same kind of fact carries the same halo everywhere. The hues themselves live
    in page.blade.php; nothing here injects colour.

    Values stay modest in size/weight so the header band and the body cards keep
    the visual lead.
--}}

@if (filled($stats))
    <div class="cs-rp-stats">
        @foreach ($stats as $stat)
            <div @class([
                'fi-section',
                'cs-rp-card',
                'cs-rp-stat',
                'cs-rp-glow' => filled($stat['glow']),
                'cs-rp-glow-' . $stat['glow'] => filled($stat['glow']),
            ])>
                @if (filled($stat['icon']))
                    <span class="cs-rp-stat-icon">
                        <x-filament::icon :icon="$stat['icon']" />
                    </span>
                @endif

                <div class="cs-rp-stat-body">
                    <span class="cs-rp-stat-label">{{ $stat['label'] }}</span>
                    <span
                        @class(['cs-rp-stat-value', 'cs-rp-ltr' => $stat['ltr']])
                        @if ($stat['ltr']) dir="ltr" @endif
                    >
                        @if (filled($stat['url']))
                            <a href="{{ $stat['url'] }}" target="_blank" rel="noopener noreferrer">{{ $stat['value'] }}</a>
                        @else
                            {{ $stat['value'] }}
                        @endif
                    </span>
                </div>
            </div>
        @endforeach
    </div>
@endif
