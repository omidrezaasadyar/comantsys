@use(App\Models\PortalRequest)
<x-filament-panels::page>
@php
    $userBox   = $this->getUserBox();
    $stats     = $this->getStats();
    $qa        = $this->getQuickActionUrls();
    $rates     = $this->getRates();

    // Localize numerals for display: Persian digits + Persian thousands
    // separator under fa; Latin as-is under en. Mirrors getDates()'s approach.
    $faDigits = function (?string $s): string {
        if ($s === null || $s === '') {
            return '—';
        }
        if (app()->getLocale() === 'fa') {
            $s = str_replace(
                ['0','1','2','3','4','5','6','7','8','9', ','],
                ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹', '٬'],
                $s
            );
        }
        return $s;
    };

    // Direction → arrow glyph + color. 'flat' shows nothing.
    $dirArrow = ['up' => '▲', 'down' => '▼', 'flat' => ''];
    $dirColor = ['up' => '#26a269', 'down' => '#e05260', 'flat' => '#8a97a8'];
    $requests  = $this->getRequests();
    $requestsTotal = $this->getRequestsTotal();
    $tasks     = $this->getTasks();
    $dates     = $this->getDates();

    // Presentational maps for the 5 KPI cards, keyed to getStats() keys.
    // count/url come from real data; these three are view-only design tokens.
    $kpi = [
        'invoices'  => ['color' => '#4a7fd6', 'trend' => '▲ ۸.۲٪',  'trendColor' => '#26a269',
            'icon' => '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/><path d="M9 13h6M9 17h4"/>',
            'spark' => '0,22 14,18 28,20 42,12 56,15 70,8 84,11 100,4'],
        'sales'     => ['color' => '#26a269', 'trend' => '▲ ۱۲.۴٪', 'trendColor' => '#26a269',
            'icon' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
            'spark' => '0,24 14,20 28,22 42,16 56,10 70,13 84,6 100,7'],
        'customers' => ['color' => '#2bb3c0', 'trend' => '▲ ۵.۱٪',  'trendColor' => '#26a269',
            'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'spark' => '0,20 14,22 28,16 42,18 56,12 70,14 84,9 100,10'],
        'suppliers' => ['color' => '#8794a8', 'trend' => '＋۳ این ماه', 'trendColor' => '#9fb2d6',
            'icon' => '<path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/>',
            'spark' => '0,18 14,17 28,19 42,15 56,16 70,13 84,14 100,11'],
        'letters'   => ['color' => '#f0932b', 'trend' => '▲ ۹.۳٪',  'trendColor' => '#26a269',
            'icon' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/>',
            'spark' => '0,14 14,18 28,12 42,20 56,14 70,18 84,13 100,16'],
    ];

    // rgba helper for hex + alpha (design uses translucent tints)
    $rgba = function (string $hex, float $a): string {
        $hex = ltrim($hex, '#');
        [$r, $g, $b] = [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
        return "rgba($r,$g,$b,$a)";
    };

    $reqColors = PortalRequest::requestStatusColors();
    $reqLabels = PortalRequest::requestStatuses();
    // token color word -> hex dot color for the request rows
    $dot = ['gray' => '#8794a8', 'warning' => '#f0932b', 'info' => '#4a7fd6', 'danger' => '#e05260', 'success' => '#26a269'];

    // KPI display order (right -> left in RTL follows DOM order)
    $order = ['invoices', 'sales', 'customers', 'suppliers', 'letters'];

    // Quick actions: [key, label, icon-svg]
    $quick = [
        ['new_invoice', __('dashboard.qa_new_invoice'), '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M19 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/><path d="M12 11v6M9 14h6"/>'],
        ['proforma',    __('dashboard.qa_proforma'), '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/><path d="M9 13h6M9 17h4"/>'],
        ['customer',    __('dashboard.qa_customer'), '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>'],
        ['letter',      __('dashboard.qa_letter'), '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/>'],
        ['request',     __('dashboard.qa_request'), '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>'],
        ['reports',     __('dashboard.qa_reports'), '<path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="6"/><rect x="12" y="8" width="3" height="10"/><rect x="17" y="5" width="3" height="13"/>'],
    ];

    $clocks = [
        ['city' => __('dashboard.tz_oman'),    'flag' => '🇴🇲', 'offset' => 4],
        ['city' => __('dashboard.tz_america'), 'flag' => '🇺🇸', 'offset' => -4],
        ['city' => __('dashboard.tz_china'),   'flag' => '🇨🇳', 'offset' => 8],
        ['city' => __('dashboard.tz_england'), 'flag' => '🇬🇧', 'offset' => 1],
        ['city' => __('dashboard.tz_germany'), 'flag' => '🇩🇪', 'offset' => 2],
    ];
@endphp

    <div class="cs-dash" dir="rtl">

        {{-- welcome + clocks --}}
        <section class="cs-card cs-welcome">
            <div class="cs-welcome-user">
                <div class="cs-avatar">{{ $userBox['initial'] }}</div>
                <div>
                    <div class="cs-welcome-hi">{{ __('filament-panels::widgets/account-widget.welcome', ['app' => config('app.name')]) }}</div>
                    <div class="cs-welcome-name">{{ $userBox['name'] }}</div>
                </div>
                <a href="{{ filament()->getLogoutUrl() }}" class="cs-logout"
                   onclick="event.preventDefault();document.getElementById('cs-logout-form').submit();">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                    {{ __('filament-panels::widgets/account-widget.actions.logout.label') }}
                </a>
                <form id="cs-logout-form" method="POST" action="{{ filament()->getLogoutUrl() }}" style="display:none;">@csrf</form>
            </div>

            <div class="cs-divider-v"></div>

            <div class="cs-clockwrap">
                <div class="cs-clock-head">
                    <div class="cs-clock-title">
                        <span class="cs-dot-live"></span>
                        <span>{{ __('dashboard.world_clocks') }}</span>
                    </div>
                    <div class="cs-chips">
                        <div class="cs-chip" title="USD — TGJU">
                            <span class="cs-chip-badge" style="background:{{ $rgba('#26a269',0.16) }};color:#4cc48d;">$</span>
                            <span class="cs-chip-k">{{ __('dashboard.currency_usd') }}</span>
                            <b class="cs-chip-v">{{ $faDigits($rates['usd']['value'] ?? null) }}</b>
                            <span class="cs-chip-u">{{ __('dashboard.currency_unit') }}</span>
                            @if (($rates['usd']['dir'] ?? 'flat') !== 'flat' && ($rates['usd']['delta'] ?? '') !== '')
                                <span style="font-size:10px;font-weight:700;color:{{ $dirColor[$rates['usd']['dir']] }};">{{ $dirArrow[$rates['usd']['dir']] }}{{ $faDigits($rates['usd']['delta']) }}٪</span>
                            @endif
                        </div>
                        <div class="cs-chip" title="EUR — TGJU">
                            <span class="cs-chip-badge" style="background:{{ $rgba('#4a7fd6',0.16) }};color:#7ba4e8;">€</span>
                            <span class="cs-chip-k">{{ __('dashboard.currency_eur') }}</span>
                            <b class="cs-chip-v">{{ $faDigits($rates['eur']['value'] ?? null) }}</b>
                            <span class="cs-chip-u">{{ __('dashboard.currency_unit') }}</span>
                            @if (($rates['eur']['dir'] ?? 'flat') !== 'flat' && ($rates['eur']['delta'] ?? '') !== '')
                                <span style="font-size:10px;font-weight:700;color:{{ $dirColor[$rates['eur']['dir']] }};">{{ $dirArrow[$rates['eur']['dir']] }}{{ $faDigits($rates['eur']['delta']) }}٪</span>
                            @endif
                        </div>
                        <div class="cs-chip cs-chip-date">
                            <span style="color:#f7943e;font-size:15px;">📅</span>
                            <span class="cs-chip-k">{{ __('dashboard.date_shamsi') }} <b class="cs-chip-v">{{ $dates['jalali'] }}</b></span>
                            <span class="cs-chip-sep"></span>
                            <span class="cs-chip-k">{{ __('dashboard.date_gregorian') }} <b class="cs-chip-v">{{ now()->format('Y/m/d') }}</b></span>
                        </div>
                    </div>
                </div>
                <div class="cs-clocks">
                    @foreach ($clocks as $c)
                        <div class="cs-clock-cell">
                            <div class="cs-clock" data-clock data-offset="{{ $c['offset'] }}">
                                <span class="cs-tick cs-tick-t"></span><span class="cs-tick cs-tick-b"></span>
                                <span class="cs-tick cs-tick-r"></span><span class="cs-tick cs-tick-l"></span>
                                <span class="cs-h" data-h></span><span class="cs-m" data-m></span><span class="cs-s" data-s></span>
                                <span class="cs-cap"></span>
                            </div>
                            <div class="cs-clock-label">
                                <div style="font-size:13px;">{{ $c['flag'] }}</div>
                                <div class="cs-clock-city">{{ $c['city'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- KPI row --}}
        <section class="cs-kpis">
            @foreach ($order as $key)
                @php $v = $kpi[$key]; $s = $stats[$key]; @endphp
                <a href="{{ $s['url'] }}" class="cs-kpi">
                    <div class="cs-kpi-bar" style="background:{{ $v['color'] }};"></div>
                    <div class="cs-kpi-top">
                        <span class="cs-kpi-icon" style="background:{{ $rgba($v['color'],0.16) }};color:{{ $v['color'] }};">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">{!! $v['icon'] !!}</svg>
                        </span>
                        <span class="cs-kpi-trend" style="color:{{ $v['trendColor'] }};background:{{ $rgba($v['trendColor'],0.12) }};">{{ $v['trend'] }}</span>
                    </div>
                    <div class="cs-kpi-count">{{ $s['count'] }}</div>
                    <div class="cs-kpi-label">{{ __('dashboard.stat_' . $key) }}</div>
                    <svg width="100%" height="22" viewBox="0 0 100 30" preserveAspectRatio="none" style="margin-top:8px;display:block;">
                        <polyline points="{{ $v['spark'] }}" fill="none" stroke="{{ $v['color'] }}" stroke-width="1.6"/>
                        <polyline points="{{ $v['spark'] }} 100,30 0,30" fill="{{ $rgba($v['color'],0.1) }}" stroke="none"/>
                    </svg>
                </a>
            @endforeach
        </section>

        {{-- quick actions --}}
        <section class="cs-card">
            <h3 class="cs-h3">{{ __('dashboard.quick_actions') }}</h3>
            <div class="cs-qa">
                @foreach ($quick as [$k, $label, $icon])
                    <a href="{{ $qa[$k] }}" class="cs-qa-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">{!! $icon !!}</svg>
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        {{-- requests + tasks --}}
        <section class="cs-two">
            {{-- received requests --}}
            <div class="cs-card cs-panel">
                <div class="cs-panel-head">
                    <h3 class="cs-h3">{{ __('dashboard.received_requests') }}</h3>
                    <span class="cs-pill">{{ __('dashboard.requests_count', ['count' => $requestsTotal]) }}</span>
                </div>
                <div class="cs-scroll">
                    @forelse ($requests as $r)
                        @php
                            $color = $reqColors[$r['status']] ?? 'gray';
                            $hex = $dot[$color] ?? '#8794a8';
                        @endphp
                        <div class="cs-req">
                            <span class="cs-req-dot" style="background:{{ $hex }};box-shadow:0 0 0 3px {{ $rgba($hex,0.15) }};"></span>
                            <div class="cs-req-title">{{ $r['title'] }}</div>
                            <span class="cs-req-meta">{{ $r['who'] }} · {{ $r['ago'] }}</span>
                            <span class="cs-req-badge" style="color:{{ $hex }};background:{{ $rgba($hex,0.12) }};">{{ $reqLabels[$r['status']] ?? $r['status'] }}</span>
                        </div>
                    @empty
                        <div class="cs-empty">{{ __('dashboard.requests_empty') }}</div>
                    @endforelse
                </div>
                <div class="cs-panel-foot">
                    <a href="{{ $qa['request'] }}">{{ __('dashboard.manage_all_requests') }}</a>
                </div>
            </div>

            {{-- today's tasks --}}
            <div class="cs-card cs-panel">
                <div class="cs-panel-head">
                    <h3 class="cs-h3">{{ __('dashboard.todays_tasks') }}</h3>
                    <span class="cs-panel-date">{{ $dates['jalali_long'] }}</span>
                </div>
                <div class="cs-scroll">
                    @foreach ($tasks as $t)
                        <div class="cs-task" style="border-inline-start:3px solid {{ $t['color'] }};">
                            <div class="cs-task-time" style="color:{{ $t['color'] }};">{{ $t['time'] }}</div>
                            <div class="cs-task-title">{{ $t['title'] }}</div>
                            <div class="cs-task-sub">{{ $t['sub'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    <style>
        .cs-dash{display:flex;flex-direction:column;gap:14px;color:#e8edf4;font-family:inherit;}
        .cs-dash a{text-decoration:none;}
        .cs-card{background:linear-gradient(160deg,rgba(23,33,50,.7),rgba(14,20,31,.5));border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:16px 18px;}
        .cs-h3{margin:0;font-size:16px;font-weight:700;color:#fff;}

        /* welcome + clocks */
        .cs-welcome{display:grid;grid-template-columns:290px 1px 1fr;gap:22px;align-items:stretch;padding:16px 22px;}
        .cs-welcome-user{display:flex;align-items:center;flex-wrap:wrap;gap:12px 14px;}
        .cs-avatar{width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#3f6eaa,#284a78);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;color:#fff;box-shadow:0 8px 20px rgba(40,74,120,.4);}
        .cs-welcome-hi{font-size:13px;color:#8a97a8;margin-bottom:4px;}
        .cs-welcome-name{font-size:20px;font-weight:800;color:#fff;}
        .cs-logout{margin-inline-start:auto;display:inline-flex;align-items:center;gap:7px;white-space:nowrap;background:rgba(245,106,28,.12);border:1px solid rgba(245,106,28,.28);color:#f7943e;padding:9px 15px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s,border-color .15s;}
        .cs-logout:hover{background:rgba(245,106,28,.2);border-color:rgba(245,106,28,.45);}
        .cs-divider-v{width:1px;background:rgba(255,255,255,.09);}
        .cs-clockwrap{display:flex;flex-direction:column;}
        .cs-clock-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:nowrap;}
        .cs-clock-title{display:flex;align-items:center;gap:8px;font-size:13px;color:#9fb2d6;font-weight:600;white-space:nowrap;}
        .cs-dot-live{width:7px;height:7px;border-radius:50%;background:#4cc48d;box-shadow:0 0 0 3px rgba(76,196,141,.18);}
        .cs-chips{display:flex;align-items:stretch;gap:8px;flex-wrap:nowrap;overflow-x:auto;}
        .cs-chip{display:flex;align-items:center;gap:7px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:10px;padding:7px 11px;}
        .cs-chip-badge{width:16px;height:16px;border-radius:50%;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;}
        .cs-chip-k{font-size:12px;color:#8a97a8;white-space:nowrap;}
        .cs-chip-v{color:#e2e9f2;font-weight:700;font-size:12.5px;font-variant-numeric:tabular-nums;}
        .cs-chip-u{font-size:10px;color:#8a97a8;}
        .cs-chip-date{gap:10px;padding:7px 13px;flex-shrink:0;}
        .cs-chip-sep{width:1px;height:16px;background:rgba(255,255,255,.12);}
        .cs-clocks{flex:1;display:grid;grid-template-columns:repeat(5,1fr);gap:8px;align-items:center;}
        .cs-clock-cell{display:flex;flex-direction:column;align-items:center;gap:7px;min-width:0;background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:10px 6px;}
        .cs-clock{position:relative;flex-shrink:0;width:52px;height:52px;border-radius:50%;background:radial-gradient(circle at 50% 40%,rgba(63,110,170,.12),rgba(10,15,24,.6));border:1px solid rgba(255,255,255,.14);box-shadow:inset 0 0 12px rgba(0,0,0,.4);}
        .cs-tick{position:absolute;background:rgba(255,255,255,.2);}
        .cs-tick-t{top:4px;left:50%;transform:translateX(-50%);width:2px;height:4px;background:rgba(255,255,255,.35);}
        .cs-tick-b{bottom:4px;left:50%;transform:translateX(-50%);width:2px;height:4px;}
        .cs-tick-r{top:50%;right:4px;transform:translateY(-50%);width:4px;height:2px;}
        .cs-tick-l{top:50%;left:4px;transform:translateY(-50%);width:4px;height:2px;}
        .cs-h{position:absolute;left:50%;bottom:50%;width:2.5px;height:11px;border-radius:3px;background:#cbd5e2;transform-origin:50% 100%;transform:translateX(-50%);}
        .cs-m{position:absolute;left:50%;bottom:50%;width:2px;height:17px;border-radius:3px;background:#eef3fa;transform-origin:50% 100%;transform:translateX(-50%);}
        .cs-s{position:absolute;left:50%;bottom:50%;width:1px;height:20px;background:#f56a1c;transform-origin:50% 100%;transform:translateX(-50%);}
        .cs-cap{position:absolute;top:50%;left:50%;width:6px;height:6px;border-radius:50%;background:#f56a1c;transform:translate(-50%,-50%);box-shadow:0 0 0 2px rgba(10,15,24,.7);}
        .cs-clock-label{text-align:center;line-height:1.45;min-width:0;}
        .cs-clock-city{font-size:11px;color:#8a97a8;white-space:nowrap;}

        /* KPI */
        .cs-kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;}
        .cs-kpi{position:relative;overflow:hidden;display:block;background:linear-gradient(160deg,rgba(23,33,50,.75),rgba(14,20,31,.55));border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:14px;transition:transform .18s,border-color .18s,box-shadow .18s;}
        .cs-kpi:hover{transform:translateY(-2px);border-color:rgba(255,255,255,.14);box-shadow:0 10px 26px rgba(0,0,0,.35);}
        .cs-kpi-bar{position:absolute;inset-inline-start:0;top:0;bottom:0;width:3px;}
        .cs-kpi-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
        .cs-kpi-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;}
        .cs-kpi-trend{font-size:12px;font-weight:700;padding:3px 8px;border-radius:6px;}
        .cs-kpi-count{font-size:20px;font-weight:800;color:#fff;line-height:1;}
        .cs-kpi-label{font-size:13px;color:#8a97a8;margin-top:6px;}

        /* quick actions */
        .cs-qa{display:grid;grid-template-columns:repeat(6,1fr);gap:10px;margin-top:14px;}
        .cs-qa-btn{display:flex;flex-direction:column;align-items:center;gap:8px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:11px 8px;color:#cdd8e6;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;text-align:center;}
        .cs-qa-btn:hover{background:rgba(245,106,28,.1);border-color:rgba(245,106,28,.3);color:#f7943e;transform:translateY(-2px);}

        /* two-column panels */
        .cs-two{display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:stretch;}
        .cs-panel{display:flex;flex-direction:column;height:340px;padding:14px 18px;}
        .cs-panel-head{flex:none;display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
        .cs-pill{font-size:12px;color:#f0932b;font-weight:700;background:rgba(240,147,43,.12);padding:3px 10px;border-radius:20px;}
        .cs-panel-date{font-size:12px;color:#6f7d94;}
        .cs-scroll{flex:1;min-height:0;overflow-y:auto;display:flex;flex-direction:column;gap:10px;padding-inline-end:6px;}
        .cs-scroll::-webkit-scrollbar{width:6px;}
        .cs-scroll::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12);border-radius:6px;}
        .cs-panel-foot{flex:none;text-align:center;padding-top:14px;margin-top:6px;border-top:1px solid rgba(255,255,255,.06);}
        .cs-panel-foot a{font-size:13px;font-weight:600;color:#f7943e;}
        .cs-req{display:flex;align-items:center;gap:12px;border-radius:8px;padding:6px;margin:0 -6px;transition:background .15s;}
        .cs-req:hover{background:rgba(245,106,28,.06);}
        .cs-req-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
        .cs-req-title{flex:1;min-width:0;font-size:13px;color:#e8edf4;font-weight:600;}
        .cs-req-meta{font-size:11px;color:#7c8aa0;flex-shrink:0;}
        .cs-req-badge{font-size:11px;font-weight:600;padding:3px 8px;border-radius:6px;flex-shrink:0;}
        .cs-empty{flex:1;display:flex;align-items:center;justify-content:center;color:#6f7d94;font-size:13px;text-align:center;padding:24px 0;}
        .cs-task{display:flex;align-items:center;gap:14px;border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:12px 14px;background:rgba(255,255,255,.02);flex:none;}
        .cs-task-time{font-size:13px;font-weight:700;font-variant-numeric:tabular-nums;flex-shrink:0;width:42px;}
        .cs-task-title{flex:1;min-width:0;font-size:13px;color:#e8edf4;font-weight:600;}
        .cs-task-sub{font-size:12px;color:#8a97a8;flex-shrink:0;}

        @media (max-width:1100px){
            .cs-welcome{grid-template-columns:1fr;}
            .cs-divider-v{display:none;}
            .cs-kpis{grid-template-columns:repeat(2,1fr);}
            .cs-qa{grid-template-columns:repeat(3,1fr);}
            .cs-two{grid-template-columns:1fr;}
        }
    </style>

    <script>
    (function () {
        function tick() {
            var now = new Date();
            var utcMs = now.getTime() + now.getTimezoneOffset() * 60000;
            document.querySelectorAll('[data-clock]').forEach(function (el) {
                var off = parseFloat(el.getAttribute('data-offset'));
                var d = new Date(utcMs + off * 3600000);
                var h = d.getHours(), m = d.getMinutes(), s = d.getSeconds();
                function set(sel, deg) { var n = el.querySelector(sel); if (n) n.style.transform = 'translateX(-50%) rotate(' + deg + 'deg)'; }
                set('[data-h]', (h % 12) * 30 + m * 0.5);
                set('[data-m]', m * 6 + s * 0.1);
                set('[data-s]', s * 6);
            });
        }
        tick();
        setInterval(tick, 1000);
    })();
    </script>
</x-filament-panels::page>
