<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Concerns\HasRecordPageLayout;
use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewCompany extends ViewRecord
{
    use HasRecordPageLayout;

    /**
     * Label maps carried over verbatim from CompanyInfolist so the view and the
     * form never drift apart.
     *
     * @var array<string, string>
     */
    protected const CURRENCIES = [
        'IRR' => 'ریال',
        'EUR' => 'یورو',
        'USD' => 'دلار',
        'GBP' => 'پوند',
    ];

    /** @var array<string, string> */
    protected const LOCALES = [
        'fa' => 'فارسی (شمسی، راست‌چین)',
        'en' => 'انگلیسی (میلادی، چپ‌چین)',
    ];

    protected static string $resource = CompanyResource::class;

    /**
     * @return array<string, mixed>
     */
    protected function getRecordPageSchema(): array
    {
        /** @var Company $company */
        $company = $this->getRecord();

        return [
            'header' => [
                // Logo lives on the private disk; the signed temporary URL is
                // the same path Filament's ImageEntry takes.
                'image' => $this->privateFileUrl($company->logo_path),
                'icon' => Heroicon::OutlinedBuildingOffice2,
                'title' => $company->name,
                'badge' => filled($company->locale)
                    ? [
                        'label' => self::LOCALES[$company->locale] ?? $company->locale,
                        'color' => $company->locale === 'fa' ? 'primary' : 'info',
                    ]
                    : null,
                'subtitle' => $this->joinFilled(['شرکت', $company->name_en], ' · '),
                'breadcrumbs' => [
                    ['label' => 'شرکت‌ها', 'url' => CompanyResource::getUrl('index')],
                    ['label' => $company->name, 'url' => null],
                ],
                'edit_url' => CompanyResource::canEdit($company)
                    ? CompanyResource::getUrl('edit', ['record' => $company])
                    : null,
            ],

            'stats' => [
                [
                    'icon' => Heroicon::OutlinedIdentification,
                    'label' => 'شناسه ملی',
                    'value' => $company->national_id,
                    'ltr' => true,
                    'glow' => 'sky',
                ],
                [
                    'icon' => Heroicon::OutlinedHashtag,
                    'label' => 'کد اقتصادی',
                    'value' => $company->economic_code,
                    'ltr' => true,
                    'glow' => 'sky',
                ],
                [
                    'icon' => Heroicon::OutlinedPhone,
                    'label' => 'تلفن ثابت',
                    'value' => $company->phone,
                    'ltr' => true,
                    'glow' => 'green',
                ],
                [
                    'icon' => Heroicon::OutlinedCurrencyDollar,
                    'label' => 'ارز پیش‌فرض',
                    'value' => self::CURRENCIES[$company->default_currency] ?? null,
                    'glow' => 'violet',
                ],
            ],

            // Company has no relation managers, so the body is built from
            // explicit grid rows rather than the default panel + side-cards
            // pair. Under RTL the FIRST block in a row is the RIGHTMOST column.
            //
            //   Row 1 — 1.5fr 1fr : [فاکتورهای صادرشده]  [نشانی]
            //                        right                left      (unchanged)
            //   Row 2 — 1.2fr 1.2fr 1fr :
            //           [اطلاعات تماس] [وب و تنظیمات سند] [لوگوی شرکت]
            //
            // Every block in a row is rendered with grow, and .cs-rp-colgrid is
            // `align-items: stretch`, so the three boxes in row 2 are equal
            // height and end on the same line.
            'rows' => [
                [
                    [
                        'width' => '1.5fr',
                        'type' => 'table',
                        'heading' => 'فاکتورهای صادرشده',
                        'empty' => 'فاکتوری برای این شرکت ثبت نشده است.',
                        // PHYSICAL alignments on purpose. The logical `start` /
                        // `end` resolve to OPPOSITE edges in an RTL <th> and in
                        // an LTR-isolated <td>, which is exactly what pulled the
                        // headers off their figures. `right` / `center` / `left`
                        // pin both to the same edge. The fixed widths are set on
                        // the column, and items-table.blade.php prints the very
                        // same width on the <th> and on every <td> beneath it,
                        // so the tracks cannot drift apart.
                        'columns' => [
                            // No width: the number column absorbs whatever the
                            // two fixed tracks leave.
                            ['label' => 'شماره', 'align' => 'right', 'ltr' => true],
                            ['label' => 'تاریخ', 'align' => 'center', 'ltr' => true, 'width' => '7rem'],
                            // Sized for a full-rial grand total («32,010,000,000»
                            // = 14 characters) plus cell padding, and `cs-rp-fixed`
                            // keeps it on one line.
                            ['label' => 'مبلغ کل', 'align' => 'left', 'ltr' => true, 'width' => '8.5rem'],
                        ],
                        'rows' => $company->invoices()
                            ->latest('invoice_date')
                            ->get()
                            ->map(fn ($invoice): array => [
                                $invoice->invoice_number,
                                $this->toJalali($invoice->invoice_date),
                                $this->formatNumber($invoice->grand_total),
                            ])
                            ->all(),
                    ],
                    [
                        'width' => '1fr',
                        'type' => 'card',
                        'heading' => 'نشانی',
                        'rows' => [
                            ['label' => 'آدرس (فارسی)', 'value' => $company->address, 'long' => true],
                            ['label' => 'آدرس (انگلیسی)', 'value' => $company->address_en, 'long' => true, 'ltr' => true],
                        ],
                    ],
                ],

                // Row 2 — three equal-height boxes.
                [
                    // Single-column label/value rows: the box is narrow now, so
                    // the two-column grid this card used while it spanned the
                    // wide column would squeeze both sides.
                    [
                        'width' => '1.2fr',
                        'type' => 'card',
                        'heading' => 'اطلاعات تماس',
                        'rows' => [
                            ['label' => 'موبایل', 'value' => $company->mobile, 'ltr' => true],
                            ['label' => 'پیام‌رسان', 'value' => $company->messenger_phone, 'ltr' => true],
                            [
                                'label' => 'ایمیل',
                                'value' => $company->email,
                                'ltr' => true,
                                // Renders through the card's anchor branch, which
                                // is what gives the address the primary accent.
                                'url' => filled($company->email) ? 'mailto:' . $company->email : null,
                            ],
                            ['label' => 'کد پستی', 'value' => $company->postal_code, 'ltr' => true],
                            ['label' => 'شمارهٔ ثبت', 'value' => $company->registration_no, 'ltr' => true],
                        ],
                    ],
                    [
                        'width' => '1.2fr',
                        'type' => 'card',
                        'heading' => 'وب و تنظیمات سند',
                        'rows' => [
                            [
                                'label' => 'وب‌سایت',
                                'value' => $company->website,
                                'ltr' => true,
                                'url' => $this->externalUrl($company->website),
                            ],
                            [
                                'label' => 'آدرس پایهٔ تأیید سند (برای QR)',
                                'value' => $company->verify_url_base,
                                'ltr' => true,
                                'url' => $this->externalUrl($company->verify_url_base),
                            ],
                            ['label' => 'پاورقی PDF', 'value' => $company->footer_note, 'long' => true],
                        ],
                    ],
                    // Same private-disk path the header band's logo takes:
                    // privateFileUrl() mirrors Filament's ImageEntry — exists()
                    // guard, then a signed temporaryUrl (the `local` disk sets
                    // 'serve' => true, which is what registers the temporary-URL
                    // callback). It returns null when the file is missing, so a
                    // stale DB path falls through to the icon placeholder.
                    [
                        'width' => '1fr',
                        'type' => 'image',
                        'heading' => 'لوگوی شرکت',
                        'url' => $this->privateFileUrl($company->logo_path),
                        'alt' => $company->name,
                        'icon' => Heroicon::OutlinedBuildingOffice2,
                        'empty' => 'لوگویی برای این شرکت بارگذاری نشده است.',
                    ],
                ],
            ],
        ];
    }
}
