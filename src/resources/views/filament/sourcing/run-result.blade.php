@php
    /** @var \App\Models\SourcingRun $record */
    $results   = is_array($record->results) ? $record->results : [];
    $suppliers = is_array($results['suppliers'] ?? null) ? $results['suppliers'] : [];
    $summary   = $results['summary'] ?? null;

    $emdash = '—';
@endphp

<div class="srun-wrap" dir="rtl">
    @if ($record->status === 'failed')
        <div class="srun-error">
            <div class="srun-error-label">{{ __('sourcing.run.field.error') }}</div>
            <pre class="srun-error-text" dir="ltr">{{ $record->error ?: $emdash }}</pre>
        </div>
    @elseif ($record->status === 'completed')
        @if (count($suppliers) > 0)
            <ol class="srun-list">
                @foreach ($suppliers as $supplier)
                    @php
                        // Feature-detect the result shape. New (10e) suppliers carry
                        // 'contact'/'price' objects; old ones carry a flat 'price_hint'.
                        $isNewShape = array_key_exists('contact', $supplier) || array_key_exists('price', $supplier);

                        $contact = is_array($supplier['contact'] ?? null) ? $supplier['contact'] : [];
                        $email    = filled($contact['email'] ?? null) ? $contact['email'] : null;
                        $phone    = filled($contact['phone'] ?? null) ? $contact['phone'] : null;
                        $whatsapp = filled($contact['whatsapp'] ?? null) ? $contact['whatsapp'] : null;
                        $hasContact = $email || $phone || $whatsapp;

                        $price = is_array($supplier['price'] ?? null) ? $supplier['price'] : [];
                        $priceValue    = ($price['value'] ?? null);
                        $priceCurrency = filled($price['currency'] ?? null) ? $price['currency'] : null;
                        $priceNote     = filled($price['note'] ?? null) ? $price['note'] : null;
                        $hasPriceValue = $priceValue !== null && $priceValue !== '';
                        $hasPrice = $hasPriceValue || $priceNote;
                    @endphp

                    <li class="srun-item">
                        <div class="srun-name">{{ $supplier['name'] ?? $emdash }}</div>

                        @if (filled($supplier['url'] ?? null))
                            <a class="srun-url" href="{{ $supplier['url'] }}" target="_blank" rel="noopener noreferrer" dir="ltr">{{ $supplier['url'] }}</a>
                        @endif

                        <div class="srun-row">
                            <span class="srun-key">{{ __('sourcing.supplier.relevance') }}:</span>
                            <span class="srun-val">{{ filled($supplier['relevance'] ?? null) ? $supplier['relevance'] : $emdash }}</span>
                        </div>

                        @if ($isNewShape)
                            {{-- Contact row — each channel only when present; em-dash when none. --}}
                            <div class="srun-row">
                                <span class="srun-key">{{ __('sourcing.supplier.contact') }}:</span>
                                @if ($hasContact)
                                    <span class="srun-contact">
                                        @if ($email)
                                            <span class="srun-chip">
                                                <span class="srun-chip-key">{{ __('sourcing.supplier.email') }}</span>
                                                <a href="mailto:{{ $email }}" dir="ltr">{{ $email }}</a>
                                            </span>
                                        @endif
                                        @if ($phone)
                                            <span class="srun-chip">
                                                <span class="srun-chip-key">{{ __('sourcing.supplier.phone') }}</span>
                                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" dir="ltr">{{ $phone }}</a>
                                            </span>
                                        @endif
                                        @if ($whatsapp)
                                            <span class="srun-chip">
                                                <span class="srun-chip-key">{{ __('sourcing.supplier.whatsapp') }}</span>
                                                <span dir="ltr">{{ $whatsapp }}</span>
                                            </span>
                                        @endif
                                    </span>
                                @else
                                    <span class="srun-val">{{ $emdash }}</span>
                                @endif
                            </div>

                            {{-- Price row — value + currency + note; em-dash when none. --}}
                            <div class="srun-row">
                                <span class="srun-key">{{ __('sourcing.supplier.price') }}:</span>
                                @if ($hasPrice)
                                    <span class="srun-val" dir="ltr">{{ trim(($hasPriceValue ? $priceValue : '') . ' ' . ($priceCurrency ?? '')) ?: '' }}</span>
                                    @if ($priceNote)
                                        <span class="srun-price-note">{{ $priceNote }}</span>
                                    @endif
                                @else
                                    <span class="srun-val">{{ $emdash }}</span>
                                @endif
                            </div>
                        @else
                            {{-- Backward compatibility: old runs carried a flat price_hint. --}}
                            <div class="srun-row">
                                <span class="srun-key">{{ __('sourcing.supplier.price_hint') }}:</span>
                                <span class="srun-val">{{ filled($supplier['price_hint'] ?? null) ? $supplier['price_hint'] : $emdash }}</span>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ol>
        @else
            <div class="srun-empty">{{ __('sourcing.no_suppliers') }}</div>
        @endif

        @if (filled($summary))
            <div class="srun-summary">
                <div class="srun-summary-label">{{ __('sourcing.summary') }}</div>
                <p class="srun-summary-text">{{ $summary }}</p>
            </div>
        @endif
    @else
        <div class="srun-empty">{{ __('sourcing.no_result') }}</div>
    @endif

    {{-- Tailwind utilities do NOT apply inside custom Filament blade views (project rule) → plain scoped CSS. --}}
    <style>
        .srun-wrap { font-size: 0.9rem; line-height: 1.75; }
        .srun-list { list-style: decimal; padding-inline-start: 1.25rem; margin: 0; }
        .srun-item { padding: 0.75rem 0; border-bottom: 1px solid rgba(0, 0, 0, 0.08); }
        .srun-item:last-child { border-bottom: 0; }
        .srun-name { font-weight: 700; margin-bottom: 0.25rem; }
        .srun-url {
            display: inline-block; direction: ltr; text-align: left; unicode-bidi: embed;
            word-break: break-all; color: #2563eb; text-decoration: underline; margin-bottom: 0.35rem;
        }
        .srun-row { margin: 0.15rem 0; }
        .srun-key { font-weight: 600; opacity: 0.75; }
        .srun-contact { display: inline-flex; flex-wrap: wrap; gap: 0.35rem 0.75rem; vertical-align: top; }
        .srun-chip { display: inline-flex; align-items: baseline; gap: 0.3rem; }
        .srun-chip-key { font-weight: 600; opacity: 0.6; font-size: 0.8rem; }
        .srun-contact a { color: #2563eb; text-decoration: underline; unicode-bidi: embed; }
        .srun-price-note { opacity: 0.75; margin-inline-start: 0.4rem; }
        .srun-summary { margin-top: 1rem; padding-top: 0.75rem; border-top: 2px solid rgba(0, 0, 0, 0.12); }
        .srun-summary-label { font-weight: 700; margin-bottom: 0.35rem; }
        .srun-summary-text { margin: 0; white-space: pre-wrap; }
        .srun-empty { opacity: 0.7; padding: 0.5rem 0; }
        .srun-error-label { font-weight: 700; margin-bottom: 0.35rem; }
        .srun-error-text {
            white-space: pre-wrap; word-break: break-word; direction: ltr; text-align: left;
            background: rgba(0, 0, 0, 0.05); padding: 0.75rem; border-radius: 0.5rem; margin: 0;
            font-family: ui-monospace, monospace; font-size: 0.8rem;
        }
        .dark .srun-item { border-color: rgba(255, 255, 255, 0.1); }
        .dark .srun-summary { border-color: rgba(255, 255, 255, 0.15); }
        .dark .srun-url { color: #60a5fa; }
        .dark .srun-contact a { color: #60a5fa; }
        .dark .srun-error-text { background: rgba(255, 255, 255, 0.08); }
    </style>
</div>
