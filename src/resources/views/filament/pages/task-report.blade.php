<x-filament-panels::page>
@php
    $stats     = $this->getStats();
    $breakdown = $this->getUserBreakdown();
    $rows      = $this->getDetailRows();
    $rowCount  = count($rows);
@endphp

{{--
    Tasks report. Data comes from App\Filament\Pages\TaskReport:
      getStats()         => [total|done|pending|overdue => ['label','value','color']]
      getUserBreakdown() => [ ['name','total','done','pending','overdue'] ]  (empty unless super_admin)
      getDetailRows()    => [ ['assignee','creator','title','due','status','statusColor'] ]

    Section 2 is gated on the breakdown being non-empty rather than on
    isSuperAdmin(): that method is PRIVATE on the page class, so Blade cannot
    call it. The guard is equivalent — getUserBreakdown() already returns []
    for everyone who is not super_admin.

    Custom Blade does not go through Tailwind, so all styling is the plain-CSS
    block at the bottom. Classes are namespaced cs-rep-* so nothing can collide
    with the dashboard's own cs-kpi / cs-card / cs-task rules.
--}}

<div class="cs-rep-wrap">

    {{-- SECTION 1 — KPI cards --}}
    <div class="cs-rep-kpis">
        @foreach ($stats as $stat)
            <div class="cs-rep-kpi">
                <span class="cs-rep-kpi-bar" style="background: {{ $stat['color'] }};"></span>
                <div class="cs-rep-kpi-count" style="color: {{ $stat['color'] }};">{{ $stat['value'] }}</div>
                <div class="cs-rep-kpi-label">{{ $stat['label'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- SECTION 2 — per-assignee breakdown (super_admin only) --}}
    @if (count($breakdown) > 0)
        <div class="cs-rep-card">
            <div class="cs-rep-card-head">
                <h3 class="cs-rep-h3">تفکیک بر اساس کاربر</h3>
            </div>

            <div class="cs-rep-scroll">
                <table class="cs-rep-table">
                    <thead>
                        <tr>
                            <th>کاربر</th>
                            <th>کل</th>
                            <th>انجام‌شده</th>
                            <th>در جریان</th>
                            <th>معوق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($breakdown as $userRow)
                            <tr>
                                <td class="cs-rep-strong">{{ $userRow['name'] }}</td>
                                <td class="cs-rep-num" style="color:#4a7fd6;">{{ $userRow['total'] }}</td>
                                <td class="cs-rep-num" style="color:#26a269;">{{ $userRow['done'] }}</td>
                                <td class="cs-rep-num" style="color:#f0932b;">{{ $userRow['pending'] }}</td>
                                <td class="cs-rep-num" style="color:#e05260;">{{ $userRow['overdue'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- SECTION 3 — detail list --}}
    <div class="cs-rep-card">
        <div class="cs-rep-card-head">
            <h3 class="cs-rep-h3">فهرست پیگیری‌ها</h3>
        </div>

        @if ($rowCount === 0)
            <div class="cs-rep-empty">پیگیری‌ای برای نمایش وجود ندارد.</div>
        @else
            <div class="cs-rep-scroll">
                <table class="cs-rep-table">
                    <thead>
                        <tr>
                            <th>واگذار به</th>
                            <th>واگذار شده توسط</th>
                            <th>موضوع پیگیری</th>
                            <th>تاریخ سررسید</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="cs-rep-strong">{{ $row['assignee'] }}</td>
                                <td>{{ $row['creator'] }}</td>
                                <td class="cs-rep-title">{{ $row['title'] }}</td>
                                <td class="cs-rep-num">{{ $row['due'] }}</td>
                                <td>
                                    <span class="cs-rep-pill"
                                          style="color: {{ $row['statusColor'] }}; background: {{ $row['statusColor'] }}1f; border-color: {{ $row['statusColor'] }}40;">
                                        {{ $row['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($rowCount === 100)
                <div class="cs-rep-note">نمایش ۱۰۰ مورد اول</div>
            @endif
        @endif
    </div>

</div>

<style>
    .cs-rep-wrap {
        font-family: 'Vazirmatn', sans-serif;
        direction: rtl;
        text-align: right;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .cs-rep-kpis {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }

    .cs-rep-kpi {
        position: relative;
        overflow: hidden;
        background: linear-gradient(160deg, rgba(23, 33, 50, .75), rgba(14, 20, 31, .55));
        border: 1px solid rgba(255, 255, 255, .07);
        border-radius: 14px;
        padding: 16px 18px;
        transition: transform .18s, border-color .18s, box-shadow .18s;
    }

    .cs-rep-kpi:hover {
        transform: translateY(-2px);
        border-color: rgba(255, 255, 255, .14);
        box-shadow: 0 10px 26px rgba(0, 0, 0, .35);
    }

    .cs-rep-kpi-bar {
        position: absolute;
        inset-inline-start: 0;
        top: 0;
        bottom: 0;
        width: 3px;
    }

    .cs-rep-kpi-count {
        font-size: 26px;
        font-weight: 800;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }

    .cs-rep-kpi-label {
        font-size: 13px;
        color: #8a97a8;
        margin-top: 8px;
    }

    .cs-rep-card {
        background: linear-gradient(160deg, rgba(23, 33, 50, .75), rgba(14, 20, 31, .55));
        border: 1px solid rgba(255, 255, 255, .07);
        border-radius: 14px;
        padding: 16px 18px;
    }

    .cs-rep-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .cs-rep-h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #fff;
    }

    .cs-rep-scroll {
        overflow-x: auto;
    }

    .cs-rep-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .cs-rep-table th {
        font-size: 11px;
        font-weight: 600;
        color: #8a97a8;
        text-align: right;
        padding: 8px 10px;
        border-bottom: 1px solid rgba(255, 255, 255, .1);
        white-space: nowrap;
    }

    .cs-rep-table td {
        color: #e8edf4;
        padding: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, .05);
        vertical-align: middle;
    }

    .cs-rep-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .cs-rep-table tbody tr:hover td {
        background: rgba(255, 255, 255, .03);
    }

    .cs-rep-strong {
        font-weight: 600;
        white-space: nowrap;
    }

    .cs-rep-title {
        min-width: 180px;
        word-break: break-word;
    }

    .cs-rep-num {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .cs-rep-pill {
        display: inline-block;
        font-size: 12px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 999px;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .cs-rep-note {
        margin-top: 10px;
        font-size: 12px;
        color: #8a97a8;
    }

    .cs-rep-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6f7d94;
        font-size: 13px;
        text-align: center;
        padding: 28px 0;
    }

    @media (max-width: 700px) {
        .cs-rep-kpis {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>
</x-filament-panels::page>
