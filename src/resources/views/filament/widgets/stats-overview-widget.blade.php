<x-filament-widgets::widget>
    <x-filament::section>
    <style>
        .cs-stats {
            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.85rem;
        }
        @media (min-width: 640px) { .cs-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .cs-stats { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
        .cs-stat {
            position: relative; display: flex; align-items: center; gap: 0.85rem;
            padding: 1.15rem; border-radius: 0.75rem; overflow: hidden;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #eef2f7; text-decoration: none;
            box-shadow: 0 1px 2px rgba(15,23,42,0.04);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            justify-content: flex-start;
            width: 100%;
        }
        .cs-stat::before {
            content: ''; position: absolute; inset-inline-start: 0; top: 0; bottom: 0;
            width: 3px; background: var(--accent);
        }
        .cs-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(15,23,42,0.12);
            border-color: rgba(148,163,184,0.4);
        }
        .dark .cs-stat {
            background: linear-gradient(180deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0.03) 100%);
            border-color: rgba(255,255,255,0.08);
            box-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        .dark .cs-stat:hover {
            box-shadow: 0 10px 24px rgba(0,0,0,0.35);
            border-color: rgba(255,255,255,0.16);
        }
        .cs-stat-icon {
            display: flex; align-items: center; justify-content: center;
            width: 3rem; height: 3rem; border-radius: 0.7rem; flex-shrink: 0;
            background: color-mix(in srgb, var(--accent) 15%, transparent);
            color: var(--accent);
        }
        .cs-stat-icon svg { width: 1.6rem; height: 1.6rem; }
        .cs-stat-body { display: flex; flex-direction: column; gap: 0.2rem; }
        .cs-stat-label { font-size: 0.82rem; color: #64748b; }
        .dark .cs-stat-label { color: #94a3b8; }
        .cs-stat-count { font-size: 1.6rem; font-weight: 800; line-height: 1; color: #0f172a; }
        .dark .cs-stat-count { color: #f1f5f9; }
    </style>

    <div class="cs-stats">
        @foreach ($cards as $card)
            <a href="{{ $card['url'] }}" class="cs-stat" style="--accent: {{ $card['color'] }};">
                <span class="cs-stat-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" />
                    </svg>
                </span>
                <span class="cs-stat-body">
                    <span class="cs-stat-label">{{ $card['label'] }}</span>
                    <span class="cs-stat-count">{{ $card['count'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
    </x-filament::section>
</x-filament-widgets::widget>
