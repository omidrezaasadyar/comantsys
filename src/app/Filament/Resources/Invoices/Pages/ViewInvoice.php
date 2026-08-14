<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Concerns\HasRecordPageLayout;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewInvoice extends ViewRecord
{
    use HasRecordPageLayout;

    /**
     * Label map carried over verbatim from InvoiceInfolist (which took it from
     * InvoiceForm) so view and form labels never drift apart. The Persian
     * currency label comes from Invoice::currencyLabel(), the model's own map.
     *
     * @var array<string, string>
     */
    protected const TYPES = [
        'proforma' => 'پیش‌فاکتور',
        'invoice' => 'فاکتور فروش',
    ];

    protected static string $resource = InvoiceResource::class;

    /**
     * @return array<string, mixed>
     */
    protected function getRecordPageSchema(): array
    {
        /** @var Invoice $invoice */
        $invoice = $this->getRecord();

        $type = self::TYPES[$invoice->type] ?? $invoice->type;
        $currency = $invoice->currencyLabel();

        return [
            'header' => [
                'icon' => Heroicon::OutlinedDocumentText,
                'title' => $invoice->invoice_number,
                'badge' => filled($invoice->type)
                    ? [
                        'label' => $type,
                        'color' => $invoice->type === 'invoice' ? 'success' : 'warning',
                    ]
                    : null,
                'subtitle' => $this->joinFilled([$type, $invoice->customer?->name], ' · '),
                'breadcrumbs' => [
                    ['label' => 'فاکتورها', 'url' => InvoiceResource::getUrl('index')],
                    ['label' => $invoice->invoice_number, 'url' => null],
                ],
                'edit_url' => InvoiceResource::canEdit($invoice)
                    ? InvoiceResource::getUrl('edit', ['record' => $invoice])
                    : null,
            ],

            'stats' => [
                [
                    'icon' => Heroicon::OutlinedBuildingOffice2,
                    'label' => 'شرکت فروشنده',
                    'value' => $invoice->company?->name,
                    'glow' => 'sky',
                ],
                [
                    'icon' => Heroicon::OutlinedUserCircle,
                    'label' => 'درخواست‌کننده (خریدار)',
                    'value' => $invoice->customer?->name,
                    'glow' => 'sky',
                ],
                [
                    'icon' => Heroicon::OutlinedCurrencyDollar,
                    'label' => 'ارز',
                    'value' => $currency,
                    'glow' => 'violet',
                ],
                [
                    'icon' => Heroicon::OutlinedBanknotes,
                    'label' => 'مبلغ کل فاکتور',
                    'value' => $this->formatNumber($invoice->grand_total),
                    'ltr' => true,
                    'glow' => 'green',
                ],
            ],

            // Explicit grid rows instead of the default items + panel + cards
            // body: row 2 needs THREE boxes, which the default 1.5fr/1fr split
            // cannot express. Under RTL the FIRST block in a row is the
            // RIGHTMOST column.
            //
            //   Row 1 — one full-width block: کالا و خدمات
            //   Row 2 — 1fr 1fr 1fr :
            //           [وضعیت و ارسال] [مشخصات سند] [توضیحات]
            //
            // page.blade.php renders every row block with grow and
            // .cs-rp-colgrid is `align-items: stretch`, so the three boxes are
            // equal height and end on the same line.
            'rows' => [
                [
                    [
                        'width' => '1fr',
                        'type' => 'table',
                        'heading' => 'کالا و خدمات (' . $currency . ')',
                        'empty' => 'قلمی برای این فاکتور ثبت نشده است.',
                        // PHYSICAL alignments, not the logical `start` / `end`:
                        // those resolve to OPPOSITE edges in an RTL <th> and in
                        // an LTR-isolated <td>, which is what pushed every
                        // figure out from under its header. `right` / `center` /
                        // `left` pin both to the same edge, and
                        // items-table.blade.php prints each column's width on the
                        // <th> and on every <td> beneath it, so the header and
                        // body tracks cannot drift apart.
                        //
                        // `amount_width` additionally pins the LAST column
                        // («قیمت فروش») to a fixed track — the normaliser forces
                        // its alignment to `left` — and `.cs-rp-amount-col`
                        // reuses that same `--cs-amount-w` for the totals block,
                        // so جمع کل فروش / جمع مالیات / مبلغ کل فاکتور end on the
                        // same edge as the amounts above them.
                        'amount_width' => '8.5rem',
                        'columns' => [
                            ['label' => 'کد کالا', 'align' => 'right', 'ltr' => true, 'width' => '5.5rem'],
                            // The only column with no width: it absorbs whatever
                            // the four fixed tracks leave.
                            ['label' => 'شرح کالا و خدمات', 'align' => 'right'],
                            ['label' => 'تعداد', 'align' => 'center', 'ltr' => true, 'width' => '5rem'],
                            // Widest real figure across all invoices is
                            // «29,100,000,000» (14 characters); 8.5rem covers it
                            // plus cell padding, and `cs-rp-fixed` keeps it on
                            // one line.
                            ['label' => 'قیمت واحد', 'align' => 'left', 'ltr' => true, 'width' => '8.5rem'],
                            ['label' => 'قیمت فروش', 'align' => 'left', 'ltr' => true],
                        ],
                        'rows' => $invoice->items
                            ->map(fn ($item): array => [
                                $item->item_code,
                                $item->description,
                                $this->formatNumber($item->quantity, decimals: 2, trimZeros: true),
                                $this->formatNumber($item->unit_price),
                                $this->formatNumber($item->net_sales),
                            ])
                            ->all(),
                        'totals' => [
                            ['label' => 'جمع کل فروش (' . $currency . ')', 'value' => $this->formatNumber($invoice->subtotal)],
                            ['label' => 'جمع مالیات و عوارض', 'value' => $this->formatNumber($invoice->vat_amount)],
                            ['label' => 'مبلغ کل فاکتور', 'value' => $this->formatNumber($invoice->grand_total)],
                        ],
                    ],
                ],

                // Row 2 — three equal-height boxes.
                [
                    // Phase 3: the delivery / approval / revision fields added
                    // in Phase 1-2. Labels and colours come ONLY from the
                    // model's own maps, the same source the form's Selects
                    // build their options from, so a value can never render
                    // with a label the form would not accept. Every row is
                    // printed even when empty — a stable six-row box beats one
                    // whose height jumps as fields get filled in.
                    [
                        'width' => '1fr',
                        'type' => 'card',
                        'heading' => 'وضعیت و ارسال',
                        'rows' => [
                            [
                                'label' => 'وضعیت ارسال',
                                'value' => Invoice::sendStatuses()[$invoice->send_status] ?? $invoice->send_status,
                                'badge' => Invoice::sendStatusColors()[$invoice->send_status] ?? 'gray',
                            ],
                            [
                                'label' => 'وضعیت تأیید',
                                'value' => Invoice::approvalStatuses()[$invoice->approval_status] ?? $invoice->approval_status,
                                'badge' => Invoice::approvalStatusColors()[$invoice->approval_status] ?? 'gray',
                            ],
                            [
                                // The card layout has no boolean row (same
                                // reason the infolist has no TextEntry->boolean()),
                                // so the flag is rendered as a badge like the
                                // two statuses above it.
                                'label' => 'ویرایش پس از ارسال',
                                'value' => $invoice->is_revised ? 'بله' : 'خیر',
                                'badge' => $invoice->is_revised ? 'info' : 'gray',
                            ],
                            ['label' => 'تاریخ ارسال', 'value' => $this->toJalali($invoice->sent_at), 'ltr' => true],
                            ['label' => 'تاریخ ویرایش', 'value' => $this->toJalali($invoice->revised_at), 'ltr' => true],
                            ['label' => 'شخص دریافت‌کننده', 'value' => $invoice->recipient_person],
                        ],
                    ],
                    [
                        'width' => '1fr',
                        'type' => 'card',
                        'heading' => 'مشخصات سند',
                        'rows' => [
                            ['label' => 'نام کارشناس', 'value' => $invoice->expert_name],
                            ['label' => 'شماره استعلام', 'value' => $invoice->inquiry_number, 'ltr' => true],
                            ['label' => 'تاریخ استعلام', 'value' => $this->toJalali($invoice->inquiry_date), 'ltr' => true],
                            ['label' => 'تاریخ فاکتور', 'value' => $this->toJalali($invoice->invoice_date), 'ltr' => true],
                            [
                                'label' => 'نرخ ارزش افزوده (٪)',
                                // cast keeps vat_rate as decimal:2, so 9.00 → «9٪».
                                'value' => filled($invoice->vat_rate)
                                    ? sprintf('%s%%', (float) $invoice->vat_rate)
                                    : null,
                                'ltr' => true,
                            ],
                        ],
                    ],
                    [
                        'width' => '1fr',
                        'type' => 'card',
                        'heading' => 'توضیحات',
                        'rows' => [
                            // No row label: the header strip above already
                            // reads «توضیحات».
                            ['value' => $invoice->notes, 'long' => true],
                        ],
                    ],
                ],
            ],
        ];
    }
}
