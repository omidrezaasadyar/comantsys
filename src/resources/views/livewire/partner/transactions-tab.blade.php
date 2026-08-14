<div class="cs-txn-wrap">
    @php($balances = $this->getBalances())

    @php($csCurrencyColors = [
        'IRR' => '#3b82f6',
        'EUR' => '#a855f7',
        'USD' => '#22c55e',
        'GBP' => '#f59e0b',
    ])

    @if (count($balances))
        <div class="cs-balances">
            @foreach ($balances as $currency => $b)
                @php($accent = $csCurrencyColors[$currency] ?? '#64748b')
                <div class="cs-bal-card" style="--cs-accent: {{ $accent }};">
                    <div class="cs-bal-head">
                        <span class="cs-bal-dot"></span>
                        <span class="cs-bal-label">
                            مانده {{ \App\Models\PartnerTransaction::currencyLabels()[$currency] ?? $currency }}
                        </span>
                    </div>
                    <div @class(['cs-bal-amount', 'cs-bal-amount--neg' => $b['realized'] < 0])>
                        {{ number_format($b['realized'], 2, '.', ',') }}
                    </div>
                    <div class="cs-bal-breakdown">
                        <span>آورده: {{ number_format($b['paid_in'], 0, '.', ',') }}</span>
                        <span>برداشت: {{ number_format($b['paid_out'], 0, '.', ',') }}</span>
                    </div>
                    @if ($b['pending_in'] > 0 || $b['pending_out'] > 0)
                        <div class="cs-bal-pending">
                            @if ($b['pending_in'] > 0)
                                <span>در انتظار واریز: {{ number_format($b['pending_in'], 0, '.', ',') }}</span>
                            @endif
                            @if ($b['pending_out'] > 0)
                                <span>در انتظار پرداخت: {{ number_format($b['pending_out'], 0, '.', ',') }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="cs-txn-table">
        {{ $this->table }}
    </div>

    <style>
        .cs-txn-wrap { display: flex; flex-direction: column; gap: 1.25rem; }

        .cs-balances {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 0.75rem;
        }

        .cs-bal-card {
            position: relative;
            padding: 0.75rem 0.9rem;
            border-radius: 0.6rem;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-inline-start: 3px solid var(--cs-accent);
        }
        .dark .cs-bal-card {
            background: color-mix(in oklab, var(--gray-900) 60%, transparent);
            border-color: var(--gray-800);
        }

        .cs-bal-head { display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.35rem; }
        .cs-bal-dot { width: 0.5rem; height: 0.5rem; border-radius: 999px; background: var(--cs-accent); flex-shrink: 0; }
        .cs-bal-label { font-size: 0.75rem; font-weight: 600; color: var(--gray-500); }
        .dark .cs-bal-label { color: var(--gray-400); }

        .cs-bal-amount { font-size: 1.15rem; font-weight: 700; color: var(--gray-900); direction: ltr; text-align: right; }
        .dark .cs-bal-amount { color: var(--gray-50); }
        .cs-bal-amount--neg { color: #f97316; }

        .cs-bal-breakdown {
            margin-top: 0.4rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.15rem 0.75rem;
            font-size: 0.7rem;
            color: var(--gray-500);
        }
        .dark .cs-bal-breakdown { color: var(--gray-400); }

        .cs-bal-pending {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--gray-200);
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            font-size: 0.7rem;
            color: var(--gray-500);
        }
        .dark .cs-bal-pending { border-color: var(--gray-800); color: var(--gray-400); }
    </style>
</div>
