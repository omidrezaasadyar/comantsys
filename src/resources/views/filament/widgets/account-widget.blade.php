@php
    $user = filament()->auth()->user();
@endphp

<x-filament-widgets::widget class="fi-account-widget">
    <x-filament::section>
        <div class="cs-account">
            {{-- Section 1 (small): user photo + welcome + username + logout --}}
            <div class="cs-acc-user">
                <img
                    class="cs-avatar"
                    src="{{ filament()->getUserAvatarUrl($user) }}"
                    alt="{{ __('filament-panels::layout.avatar.alt', ['name' => filament()->getUserName($user)]) }}"
                    loading="lazy"
                />

                <div class="cs-account-main">
                    <h2 class="cs-account-heading">
                        {{ __('filament-panels::widgets/account-widget.welcome', ['app' => config('app.name')]) }}
                    </h2>
                    <p class="cs-account-name">
                        {{ filament()->getUserName($user) }}
                    </p>
                </div>

                <form
                    action="{{ filament()->getLogoutUrl() }}"
                    method="post"
                    class="cs-account-logout"
                >
                    @csrf

                    <x-filament::button
                        color="gray"
                        :icon="\Filament\Support\Icons\Heroicon::ArrowLeftEndOnRectangle"
                        :icon-alias="\Filament\View\PanelsIconAlias::WIDGETS_ACCOUNT_LOGOUT_BUTTON"
                        labeled-from="sm"
                        tag="button"
                        type="submit"
                    >
                        {{ __('filament-panels::widgets/account-widget.actions.logout.label') }}
                    </x-filament::button>
                </form>
            </div>

            <div class="cs-acc-divider"></div>

            {{-- Section 2 (bigger): 5 small analog clocks with a flag in each face --}}
            <div
                class="cs-acc-clocks"
                x-data="{
                    now: new Date(),
                    zones: [
                        { label: @js(__('dashboard.tz_germany')), tz: 'Europe/Berlin',    flag: 'de' },
                        { label: @js(__('dashboard.tz_england')), tz: 'Europe/London',    flag: 'gb' },
                        { label: @js(__('dashboard.tz_china')),   tz: 'Asia/Shanghai',    flag: 'cn' },
                        { label: @js(__('dashboard.tz_america')), tz: 'America/New_York', flag: 'us' },
                        { label: @js(__('dashboard.tz_oman')),    tz: 'Asia/Muscat',      flag: 'om' },
                    ],
                    angles(tz) {
                        const parts = new Intl.DateTimeFormat('en-GB', {
                            timeZone: tz, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
                        }).formatToParts(this.now);
                        const get = (t) => parseInt(parts.find((p) => p.type === t).value, 10);
                        const h = (get('hour') % 24) % 12;
                        const m = get('minute');
                        const s = get('second');
                        return { h: h * 30 + m * 0.5, m: m * 6 + s * 0.1, s: s * 6 };
                    },
                    init() { setInterval(() => { this.now = new Date(); }, 1000); },
                }"
            >
                <template x-for="zone in zones" :key="zone.tz">
                    <div class="cs-clock">
                        <div class="cs-face">
                            <svg viewBox="0 0 100 100" class="cs-svg" aria-hidden="true">
                                <circle cx="50" cy="50" r="48" class="cs-dial" />

                                <template x-for="i in 12" :key="i">
                                    <line x1="50" y1="6" x2="50" y2="12" class="cs-tick"
                                          :transform="`rotate(${i * 30} 50 50)`" />
                                </template>

                                <line x1="50" y1="52" x2="50" y2="30" class="cs-hand cs-hand-h"
                                      :style="`transform: rotate(${angles(zone.tz).h}deg)`" />
                                <line x1="50" y1="54" x2="50" y2="20" class="cs-hand cs-hand-m"
                                      :style="`transform: rotate(${angles(zone.tz).m}deg)`" />
                                <line x1="50" y1="58" x2="50" y2="16" class="cs-hand cs-hand-s"
                                      :style="`transform: rotate(${angles(zone.tz).s}deg)`" />

                                <circle cx="50" cy="50" r="2.5" class="cs-cap" />
                            </svg>
                        </div>

                        {{-- Flag now sits in the label area, replacing the country name. --}}
                        <img class="cs-flag" :src="`https://flagcdn.com/w40/${zone.flag}.png`"
                             :alt="zone.label" loading="lazy" />
                    </div>
                </template>
            </div>
        </div>

        <style>
            .cs-account {
                display: flex;
                align-items: stretch;
            }

            /* Vertical divider between the two sections. */
            .cs-acc-divider {
                width: 1px;
                align-self: stretch;
                background: rgba(148, 163, 184, 0.25);
                margin-inline: 1.5rem;
            }
            .dark .cs-acc-divider { background: rgba(255, 255, 255, 0.1); }

            /* Section 1: small, sized to its content. */
            .cs-acc-user {
                flex: 0 0 auto;
                display: flex;
                align-items: center;
                gap: 1rem;
            }
            .cs-avatar {
                flex: 0 0 auto;
                height: 3.5rem; width: 3.5rem; aspect-ratio: 1 / 1;
                border-radius: 0.75rem; object-fit: cover;
                background: rgba(148, 163, 184, 0.15);
                box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.06), 0 2px 6px rgba(0, 0, 0, 0.12);
            }
            .cs-account-main { min-width: 0; }
            .cs-account-heading { font-size: 0.9rem; font-weight: 600; color: #64748b; }
            .dark .cs-account-heading { color: #94a3b8; }
            .cs-account-name { font-size: 1.1rem; font-weight: 700; color: #0f172a; }
            .dark .cs-account-name { color: #f1f5f9; }
            .cs-account-logout { margin-inline-start: 1rem; }

            /* Section 2: bigger, 5 analog clocks spread across it. */
            .cs-acc-clocks {
                flex: 1 1 auto;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 1rem;
                flex-wrap: wrap;
            }
            .cs-clock {
                display: flex; flex-direction: column; align-items: center; gap: 0.25rem;
            }
            .cs-face { position: relative; width: 64px; height: 64px; }
            .cs-svg { width: 100%; height: 100%; display: block; }

            .cs-dial { fill: #ffffff; stroke: rgba(148, 163, 184, 0.45); stroke-width: 2; }
            .dark .cs-dial { fill: rgba(255, 255, 255, 0.05); stroke: rgba(255, 255, 255, 0.14); }
            .cs-tick { stroke: #94a3b8; stroke-width: 1.5; stroke-linecap: round; }
            .dark .cs-tick { stroke: rgba(226, 232, 240, 0.55); }

            /* Hands pivot on the clock centre (viewBox 50,50). */
            .cs-hand { transform-box: view-box; transform-origin: 50px 50px; stroke-linecap: round; }
            .cs-hand-h { stroke: #0f172a; stroke-width: 4; transition: transform 0.3s cubic-bezier(0.4, 2.2, 0.55, 1); }
            .cs-hand-m { stroke: #334155; stroke-width: 3; transition: transform 0.3s cubic-bezier(0.4, 2.2, 0.55, 1); }
            .cs-hand-s { stroke: #ef4444; stroke-width: 1.5; transition: none; } /* tick, no reverse-sweep */
            .dark .cs-hand-h { stroke: #f1f5f9; }
            .dark .cs-hand-m { stroke: #cbd5e1; }
            .cs-cap { fill: #ef4444; }

            /* Flag in the label area (below the clock), replacing the country name. */
            .cs-flag {
                width: 22px; height: auto; border-radius: 3px;
                box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.18);
            }

            @media (max-width: 640px) {
                .cs-account { flex-direction: column; align-items: stretch; gap: 1rem; }
                .cs-acc-divider { width: auto; height: 1px; margin-inline: 0; }
            }
        </style>
    </x-filament::section>
</x-filament-widgets::widget>
