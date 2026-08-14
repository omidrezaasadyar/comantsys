<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Concerns\HasRecordPageLayout;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewCustomer extends ViewRecord
{
    use HasRecordPageLayout;

    /**
     * Same labels CustomerInfolist used for `person_type`.
     *
     * @var array<string, string>
     */
    protected const PERSON_TYPES = [
        'legal' => 'حقوقی',
        'real' => 'حقیقی',
    ];

    protected static string $resource = CustomerResource::class;

    /**
     * Customer uses the grid body ('rows'): one row of three boxes rather than
     * the default side-panel layout. Customer has no relation managers, so the
     * invoices hasMany is rendered directly as a table.
     *
     * @return array<string, mixed>
     */
    protected function getRecordPageSchema(): array
    {
        /** @var Customer $customer */
        $customer = $this->getRecord();

        $personType = self::PERSON_TYPES[$customer->person_type] ?? null;

        return [
            'header' => [
                'icon' => Heroicon::OutlinedIdentification,
                'title' => $customer->name,
                'badge' => filled($personType)
                    ? [
                        'label' => $personType,
                        'color' => $customer->person_type === 'legal' ? 'info' : 'gray',
                    ]
                    : null,
                'subtitle' => $this->joinFilled(['مشتری', $customer->national_id], ' · '),
                'breadcrumbs' => [
                    ['label' => 'مشتریان', 'url' => CustomerResource::getUrl('index')],
                    ['label' => $customer->name, 'url' => null],
                ],
                'edit_url' => CustomerResource::canEdit($customer)
                    ? CustomerResource::getUrl('edit', ['record' => $customer])
                    : null,
            ],

            'stats' => [
                [
                    'icon' => Heroicon::OutlinedIdentification,
                    'label' => 'شناسه ملی / کد ملی',
                    'value' => $customer->national_id,
                    'ltr' => true,
                    'glow' => 'sky',
                ],
                [
                    'icon' => Heroicon::OutlinedHashtag,
                    'label' => 'کد اقتصادی',
                    'value' => $customer->economic_code,
                    'ltr' => true,
                    'glow' => 'sky',
                ],
                [
                    'icon' => Heroicon::OutlinedPhone,
                    'label' => 'تلفن',
                    'value' => $customer->phone,
                    'ltr' => true,
                    'glow' => 'green',
                ],
                [
                    'icon' => Heroicon::OutlinedEnvelope,
                    'label' => 'کد پستی',
                    'value' => $customer->postal_code,
                    'ltr' => true,
                    'glow' => 'amber',
                ],
            ],

            // One row, three uneven columns. Under RTL the first block is the
            // rightmost, so this reads: نشانی · یادداشت · فاکتورها.
            'rows' => [
                [
                    [
                        'width' => '1fr',
                        'type' => 'card',
                        'heading' => 'نشانی',
                        'rows' => [
                            // No row label: the header strip already says it.
                            ['value' => $customer->address, 'long' => true],
                        ],
                    ],
                    [
                        'width' => '1fr',
                        'type' => 'card',
                        'heading' => 'یادداشت',
                        'rows' => [
                            ['value' => $customer->notes, 'long' => true],
                        ],
                    ],
                    [
                        'width' => '2fr',
                        'type' => 'table',
                        'heading' => 'فاکتورهای این مشتری',
                        'empty' => 'فاکتوری برای این مشتری ثبت نشده است.',
                        // Every column carries a fixed width AND a PHYSICAL
                        // alignment, both applied identically to the header
                        // cell and to each body cell, so a heading always sits
                        // squarely over its values.
                        //
                        // Physical (right/center/left) rather than logical
                        // (start/end) on purpose: the header cell lives in the
                        // RTL document while each value cell is dir="ltr" for
                        // digit order, so `start` would resolve to OPPOSITE
                        // edges in the two and the column would drift.
                        'columns' => [
                            ['label' => 'شماره', 'align' => 'right', 'width' => '12rem', 'ltr' => true],
                            ['label' => 'تاریخ', 'align' => 'center', 'width' => '7rem', 'ltr' => true],
                            ['label' => 'مبلغ کل', 'align' => 'left', 'width' => '11rem', 'ltr' => true],
                        ],
                        'rows' => $customer->invoices()
                            ->latest('invoice_date')
                            ->get()
                            ->map(fn ($invoice): array => [
                                $invoice->invoice_number,
                                $this->toJalali($invoice->invoice_date),
                                $this->formatNumber($invoice->grand_total),
                            ])
                            ->all(),
                    ],
                ],
            ],
        ];
    }
}
