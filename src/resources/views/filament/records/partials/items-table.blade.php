{{--
    resources/views/filament/records/partials/items-table.blade.php

    Reusable flat table: header row + body rows + optional totals block.
    Used both for the full-width line-items region (Inquiry / Invoice / Sale)
    and for tables inside the side panel (Customer / Company invoices).

    Expects $table:
      ['heading', 'columns' => [['label','align','ltr']],
       'rows' => [[ ['value','align','ltr'], … ]],
       'totals' => [['label','value','ltr']], 'empty' => '…']

    Optional $bare — set inside the side panel, where the surrounding card
    already supplies the fi-section shell and heading strip.
--}}

@php($bare = $bare ?? false)

@if ($bare)
    <div
        @class(['cs-rp-table-region', 'cs-rp-amount-col' => filled($table['amount_width'])])
        @if (filled($table['amount_width'])) style="--cs-amount-w: {{ $table['amount_width'] }};" @endif
    >
@else
    <section class="fi-section cs-rp-card cs-rp-items">
        @if (filled($table['heading']))
            <header class="fi-section-header cs-rp-detail-head">{{ $table['heading'] }}</header>
        @endif

        <div
        @class(['cs-rp-table-region', 'cs-rp-amount-col' => filled($table['amount_width'])])
        @if (filled($table['amount_width'])) style="--cs-amount-w: {{ $table['amount_width'] }};" @endif
    >
@endif

    @if (filled($table['rows']))
        <div class="cs-rp-table-scroll">
            <table class="cs-rp-table">
                <thead>
                    <tr>
                        @foreach ($table['columns'] as $column)
                            <th
                                @class(['cs-rp-fixed' => filled($column['width'])])
                                style="text-align: {{ $column['align'] }};@if (filled($column['width'])) width: {{ $column['width'] }};@endif"
                            >{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($table['rows'] as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td
                                    style="text-align: {{ $cell['align'] }};@if (filled($cell['width'])) width: {{ $cell['width'] }};@endif"
                                    @class(['cs-rp-ltr' => $cell['ltr'], 'cs-rp-fixed' => filled($cell['width'])])
                                    @if ($cell['ltr']) dir="ltr" @endif
                                >{{ $cell['value'] }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="cs-rp-empty">{{ $table['empty'] }}</p>
    @endif

    @if (filled($table['totals']))
        <div class="cs-rp-totals">
            @foreach ($table['totals'] as $total)
                <div class="cs-rp-total">
                    <span class="cs-rp-total-label">{{ $total['label'] }}</span>
                    <span
                        @class([
                            'cs-rp-total-value',
                            'cs-rp-ltr' => $total['ltr'],
                            'cs-rp-total-danger' => $total['tone'] === 'danger',
                        ])
                        @if ($total['ltr']) dir="ltr" @endif
                    >{{ $total['value'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

@if ($bare)
    </div>
@else
        </div>
    </section>
@endif
