<?php

namespace App\Filament\Resources\PartnerTransactions\Schemas;

use App\Filament\Concerns\RecordValueHelpers;
use App\Filament\Resources\PartnerTransactions\PartnerTransactionResource;
use App\Models\PartnerTransaction;
use Filament\Support\Icons\Heroicon;

/**
 * فیلد-نگاشتِ صفحهٔ نمای تراکنش مالی.
 *
 * NOTE — differs from SaleInfolist on purpose. SaleInfolist is a Filament
 * `Schema` (Section/TextEntry) wired through SaleResource::infolist(); it is
 * dead weight on the Sale view page, because ViewSale uses
 * HasRecordPageLayout, and that trait replaces the page's whole `content()`
 * slot with the custom blade. The array the blade actually renders is built in
 * ViewSale::getRecordPageSchema().
 *
 * So this class returns THAT array — the one the record-page layout consumes —
 * rather than a Schema, and ViewPartnerTransaction just delegates to it. That
 * keeps the field mapping in the Schemas/ namespace (same place SaleInfolist
 * lives) without shipping a second, never-rendered infolist.
 *
 * The array shape is documented on HasRecordPageLayout::getRecordPageSchema().
 */
class PartnerTransactionInfolist
{
    // formatNumber() / toJalali() / joinFilled() — the same helpers the page
    // trait uses, so the values here are formatted identically to ViewSale's.
    use RecordValueHelpers;

    /**
     * @return array<string, mixed>
     */
    public static function schema(PartnerTransaction $txn): array
    {
        return (new self)->build($txn);
    }

    /**
     * The helpers are protected instance methods on RecordValueHelpers, hence
     * the instance hop in schema().
     *
     * @return array<string, mixed>
     */
    protected function build(PartnerTransaction $txn): array
    {
        $typeLabel = PartnerTransaction::types()[$txn->type] ?? $txn->type;
        $currencyLabel = PartnerTransaction::currencyLabels()[$txn->currency] ?? $txn->currency;

        // Cast is decimal:2, so cents are kept when they exist and dropped when
        // they are all zeros — a rial figure stays «1,000,000», a euro one
        // keeps its fraction instead of being rounded away.
        $amount = $this->formatNumber($txn->amount, decimals: 2, trimZeros: true);

        // Both maps are nullable columns / optional states; a blank value is
        // turned into the «—» placeholder by the trait's normaliser, so nothing
        // needs a fallback string here.
        $paymentMethod = filled($txn->payment_method)
            ? (PartnerTransaction::paymentMethods()[$txn->payment_method] ?? $txn->payment_method)
            : null;

        return [
            'header' => [
                'icon' => Heroicon::OutlinedBanknotes,
                'title' => $txn->user?->name,
                // آورده → success, برداشت → danger, straight from the model's
                // own colour map (the same one the table's type badge uses).
                'badge' => filled($txn->type)
                    ? [
                        'label' => $typeLabel,
                        'color' => PartnerTransaction::typeColors()[$txn->type] ?? 'gray',
                    ]
                    : null,
                'subtitle' => $this->joinFilled(['تراکنش مالی', $txn->purpose], ' · '),
                'breadcrumbs' => [
                    ['label' => 'تراکنش‌های مالی', 'url' => PartnerTransactionResource::getUrl('index')],
                    ['label' => $txn->user?->name, 'url' => null],
                ],
                'edit_url' => PartnerTransactionResource::canEdit($txn)
                    ? PartnerTransactionResource::getUrl('edit', ['record' => $txn])
                    : null,
            ],

            'stats' => [
                [
                    'icon' => Heroicon::OutlinedBanknotes,
                    'label' => 'مبلغ',
                    // Currency rides with the figure so the number is never
                    // read against the wrong unit; «—» when there is no amount.
                    'value' => filled($amount)
                        ? $this->joinFilled([$amount, $currencyLabel], ' ')
                        : null,
                    'ltr' => true,
                    'glow' => 'green',
                ],
                [
                    'icon' => Heroicon::OutlinedArrowsRightLeft,
                    'label' => 'نوع',
                    'value' => $typeLabel,
                    'glow' => 'sky',
                ],
                [
                    'icon' => Heroicon::OutlinedCheckBadge,
                    'label' => 'وضعیت',
                    'value' => PartnerTransaction::statuses()[$txn->status] ?? $txn->status,
                    'glow' => 'violet',
                ],
                [
                    'icon' => Heroicon::OutlinedCreditCard,
                    'label' => 'روش پرداخت',
                    'value' => $paymentMethod,
                    'glow' => 'amber',
                ],
            ],

            // Grid body rather than the default items-table + side panel:
            // PartnerTransaction registers no relation managers, and the
            // default body would render an empty relations panel. Under RTL
            // the first block is the RIGHTMOST column.
            'rows' => [
                [
                    [
                        'width' => '1fr',
                        'type' => 'card',
                        'heading' => 'مشخصات تراکنش',
                        'rows' => [
                            ['label' => 'تاریخ پرداخت', 'value' => $this->toJalali($txn->txn_date), 'ltr' => true],
                            // Free text: label above, value across the card, so
                            // a long «بابت» wraps instead of squeezing the row.
                            ['label' => 'بابت', 'value' => $txn->purpose, 'long' => true],
                        ],
                    ],
                    [
                        'width' => '1fr',
                        'type' => 'card',
                        'heading' => 'حساب مقصد',
                        'rows' => [
                            ['label' => 'نام صاحب حساب', 'value' => $txn->account_holder],
                            ['label' => 'شماره حساب', 'value' => $txn->account_number, 'ltr' => true],
                        ],
                    ],
                    [
                        'width' => '1fr',
                        'type' => 'card',
                        'heading' => 'اطلاعات ثبت',
                        'rows' => [
                            ['label' => 'ثبت‌کننده', 'value' => $txn->creator?->name],
                            ['label' => 'تاریخ ثبت', 'value' => $this->toJalali($txn->created_at, withTime: true), 'ltr' => true],
                        ],
                    ],
                ],
            ],
        ];
    }
}
