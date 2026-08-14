<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Concerns\HasRecordPageLayout;
use App\Filament\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSale extends ViewRecord
{
    use HasRecordPageLayout;

    /**
     * Same currency map SaleForm/SaleInfolist use.
     *
     * @var array<string, string>
     */
    protected const CURRENCIES = [
        'IRR' => 'ریال (IRR)',
        'EUR' => 'یورو (EUR)',
        'GBP' => 'پوند (GBP)',
        'USD' => 'دلار (USD)',
    ];

    protected static string $resource = SaleResource::class;

    /**
     * Sale uses the grid body ('rows') rather than the default full-width
     * items table: a sale carries only a handful of extra-cost lines, so a
     * page-wide table would be mostly empty. Invoice and Inquiry keep the
     * full-width treatment.
     *
     * @return array<string, mixed>
     */
    protected function getRecordPageSchema(): array
    {
        /** @var Sale $sale */
        $sale = $this->getRecord();

        $currency = self::CURRENCIES[$sale->currency] ?? $sale->currency;

        // The map values already carry their own parentheses ("ریال (IRR)"),
        // so headings that wrap the label in brackets use the bare name.
        $currencyShort = trim(explode('(', $currency)[0]) ?: $currency;

        // Exchange-rate rows only apply to non-rial deals — same condition the
        // infolist put on its ->visible() closures.
        $showExchangeRates = $sale->currency !== 'IRR';

        return [
            'header' => [
                'icon' => Heroicon::OutlinedBanknotes,
                'title' => $sale->item_name,
                'badge' => filled($sale->currency)
                    ? ['label' => $currency, 'color' => 'info']
                    : null,
                'subtitle' => $this->joinFilled(['فروش', $sale->customer_name], ' · '),
                'breadcrumbs' => [
                    ['label' => 'فروش‌ها', 'url' => SaleResource::getUrl('index')],
                    ['label' => $sale->item_name, 'url' => null],
                ],
                'edit_url' => SaleResource::canEdit($sale)
                    ? SaleResource::getUrl('edit', ['record' => $sale])
                    : null,
            ],

            'stats' => [
                [
                    'icon' => Heroicon::OutlinedUserCircle,
                    'label' => 'نام مشتری',
                    'value' => $sale->customer_name,
                    'glow' => 'sky',
                ],
                [
                    'icon' => Heroicon::OutlinedCube,
                    'label' => 'تعداد',
                    'value' => $this->formatNumber($sale->quantity, decimals: 2, trimZeros: true),
                    'ltr' => true,
                    'glow' => 'sky',
                ],
                [
                    'icon' => Heroicon::OutlinedBanknotes,
                    'label' => 'درآمد',
                    'value' => $this->formatNumber($sale->revenue),
                    'ltr' => true,
                    'glow' => 'green',
                ],
                [
                    'icon' => Heroicon::OutlinedScale,
                    'label' => 'سود',
                    'value' => $this->formatNumber($sale->profit),
                    'ltr' => true,
                    'glow' => 'green',
                ],
            ],

            // Two rows. Row 1 is three unequal columns — under RTL the first
            // block is the RIGHTMOST, so it reads costs · deal meta ·
            // documents, ending at the widest panel. Notes are no longer a
            // cramped fourth column: they get their own full-width strip
            // underneath (row 2), which frees «مستندات» to take 1.6fr.
            'rows' => [
                [
                    [
                        'width' => '1fr',
                        'type' => 'table',
                        // Costs come from the `costs` hasMany — the same source
                        // SaleForm edits through Repeater->relationship('costs').
                        // Sale has no costs relation manager, so nothing here
                        // duplicates one.
                        'heading' => 'هزینه‌های جانبی (' . $currencyShort . ')',
                        'empty' => 'هزینهٔ جانبی‌ای برای این معامله ثبت نشده است.',
                        // Fixes the amount column at one width and one edge, so
                        // «مبلغ» and every figure under it (plus the totals)
                        // share a single line. «عنوان هزینه» takes the rest.
                        'amount_width' => '7rem',
                        'columns' => [
                            ['label' => 'عنوان هزینه', 'align' => 'start'],
                            ['label' => 'مبلغ', 'ltr' => true],
                        ],
                        'rows' => $sale->costs
                            ->map(fn ($cost): array => [
                                $cost->title,
                                $this->formatNumber($cost->amount),
                            ])
                            ->all(),
                        'totals' => [
                            // Short label so it stays on one line beside the
                            // fixed-width amount column; the value is still
                            // extra_costs_total.
                            ['label' => 'جمع هزینه', 'value' => $this->formatNumber($sale->extra_costs_total)],
                            ['label' => 'هزینهٔ کل', 'value' => $this->formatNumber($sale->total_cost)],
                            ['label' => 'درآمد', 'value' => $this->formatNumber($sale->revenue)],
                            [
                                'label' => 'سود',
                                'value' => $this->formatNumber($sale->profit),
                                // A loss is called out in the panel's danger colour.
                                'tone' => ((float) $sale->profit) < 0 ? 'danger' : null,
                            ],
                        ],
                    ],
                    [
                        'width' => '1.1fr',
                        'type' => 'card',
                        'heading' => 'مشخصات معامله',
                        'rows' => [
                            ['label' => 'تأمین‌کننده', 'value' => $sale->supplier?->name],
                            ['label' => 'تاریخ فروش', 'value' => $this->toJalali($sale->sale_date), 'ltr' => true],
                            ['label' => 'ارز', 'value' => $currency],
                            ['label' => 'قیمت خرید واحد', 'value' => $this->formatNumber($sale->purchase_unit_price), 'ltr' => true],
                            ['label' => 'قیمت فروش واحد', 'value' => $this->formatNumber($sale->sale_unit_price), 'ltr' => true],
                            $showExchangeRates
                                ? ['label' => 'نرخ ارز ICE (به ریال)', 'value' => $this->formatNumber($sale->exchange_rate_ice), 'ltr' => true]
                                : null,
                            $showExchangeRates
                                ? ['label' => 'نرخ ارز آزاد (به ریال)', 'value' => $this->formatNumber($sale->exchange_rate_free), 'ltr' => true]
                                : null,
                        ],
                    ],
                    // The attachments relation manager: «نام فایل» / «تاریخ
                    // آپلود» columns, «باز کردن» action and empty state all
                    // behave exactly as before. Its own inner heading is
                    // suppressed in the relation manager (this strip already
                    // says «مستندات»), and `flush` lets its search field span
                    // the panel body.
                    [
                        'width' => '1.6fr',
                        'type' => 'relations',
                        'heading' => 'مستندات',
                        'flush' => true,
                    ],
                ],

                // Row 2 — one block, so it spans the full page width. Same
                // $sale->notes source as before, just relocated. No header
                // strip: the row's own «یادداشت :» label names the field on
                // the same line as the value, which keeps the box one line
                // tall until the text is long enough to wrap inside it.
                [
                    [
                        'width' => '1fr',
                        'type' => 'card',
                        'heading' => null,
                        'rows' => [
                            ['label' => 'یادداشت :', 'value' => $sale->notes, 'inline' => true],
                        ],
                    ],
                ],
            ],
        ];
    }
}
