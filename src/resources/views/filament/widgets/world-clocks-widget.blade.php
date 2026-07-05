<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">{{ __('dashboard.world_clocks') }}</x-slot>

        <style>
            .cs-clocks {
                display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.85rem;
            }
            @media (min-width: 640px) { .cs-clocks { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            @media (min-width: 1024px) { .cs-clocks { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
            .cs-clock {
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                gap: 0.55rem; padding: 1.15rem 0.75rem; border-radius: 0.75rem;
                background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
                border: 1px solid #eef2f7;
                box-shadow: 0 1px 2px rgba(15,23,42,0.04);
                transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            }
            .cs-clock:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 24px rgba(15,23,42,0.12);
                border-color: rgba(148,163,184,0.4);
            }
            .dark .cs-clock {
                background: linear-gradient(180deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0.03) 100%);
                border-color: rgba(255,255,255,0.08);
                box-shadow: 0 1px 2px rgba(0,0,0,0.2);
            }
            .dark .cs-clock:hover {
                box-shadow: 0 10px 24px rgba(0,0,0,0.35);
                border-color: rgba(255,255,255,0.16);
            }
            .cs-clock img {
                width: 38px; height: auto; border-radius: 4px;
                box-shadow: 0 0 0 1px rgba(0,0,0,0.08), 0 2px 6px rgba(0,0,0,0.12);
            }
            .cs-clock .cs-time {
                font-family: ui-monospace, monospace; font-size: 1.3rem; font-weight: 700;
                letter-spacing: 0.06em; color: #0f172a; font-variant-numeric: tabular-nums;
            }
            .dark .cs-clock .cs-time { color: #f1f5f9; }
        </style>

        <div
            x-data="{
                now: new Date(),
                zones: [
                    { label: @js(__('dashboard.tz_germany')), tz: 'Europe/Berlin',    flag: 'de' },
                    { label: @js(__('dashboard.tz_england')), tz: 'Europe/London',    flag: 'gb' },
                    { label: @js(__('dashboard.tz_china')),   tz: 'Asia/Shanghai',    flag: 'cn' },
                    { label: @js(__('dashboard.tz_america')), tz: 'America/New_York', flag: 'us' },
                    { label: @js(__('dashboard.tz_oman')),    tz: 'Asia/Muscat',      flag: 'om' },
                ],
                time(tz) {
                    return new Intl.DateTimeFormat('en-US', {
                        timeZone: tz, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
                    }).format(this.now);
                },
                init() { setInterval(() => { this.now = new Date(); }, 1000); },
            }"
            class="cs-clocks"
        >
            <template x-for="zone in zones" :key="zone.tz">
                <div class="cs-clock">
                    <img :src="`https://flagcdn.com/w40/${zone.flag}.png`" :alt="zone.label" loading="lazy">
                    <span class="cs-time" x-text="time(zone.tz)"></span>
                </div>
            </template>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
