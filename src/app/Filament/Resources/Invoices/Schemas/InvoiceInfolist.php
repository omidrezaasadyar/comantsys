<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    /**
     * نگاشت‌های کدشده — عیناً از InvoiceForm برداشته شده‌اند (همان ترتیب منبع)
     * تا برچسب‌های نما و فرم هرگز از هم جدا نیفتند.
     *
     * @var array<string, string>
     */
    protected const TYPES = [
        'proforma' => 'پیش‌فاکتور',
        'invoice'  => 'فاکتور فروش',
    ];

    /** @var array<string, string> */
    protected const LOCALES = [
        'fa' => 'فارسی (تاریخ شمسی)',
        'en' => 'انگلیسی (تاریخ میلادی)',
    ];

    /** @var array<string, string> */
    protected const CURRENCIES = [
        'IRR' => 'ریال',
        'EUR' => 'یورو',
        'USD' => 'دلار',
        'GBP' => 'پوند',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ── اطلاعات فاکتور ──
                Section::make('اطلاعات فاکتور')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('type')
                            ->label('نوع')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): ?string => $state
                                ? (self::TYPES[$state] ?? $state)
                                : null)
                            ->color(fn (?string $state): string => $state === 'invoice' ? 'success' : 'warning'),

                        TextEntry::make('invoice_number')
                            ->label('شماره'),

                        TextEntry::make('company.name')
                            ->label('شرکت فروشنده')
                            ->placeholder('—'),

                        TextEntry::make('customer.name')
                            ->label('درخواست‌کننده (خریدار)')
                            ->placeholder('—'),

                        TextEntry::make('expert_name')
                            ->label('نام کارشناس')
                            ->placeholder('—'),

                        TextEntry::make('locale')
                            ->label('زبان فاکتور')
                            ->formatStateUsing(fn (?string $state): ?string => $state
                                ? (self::LOCALES[$state] ?? $state)
                                : null),

                        TextEntry::make('currency')
                            ->label('ارز')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): ?string => $state
                                ? (self::CURRENCIES[$state] ?? $state)
                                : null),

                        TextEntry::make('inquiry_number')
                            ->label('شماره استعلام')
                            ->placeholder('—'),

                        TextEntry::make('inquiry_date')
                            ->label('تاریخ استعلام')
                            ->jalaliDate()
                            ->placeholder('—'),

                        TextEntry::make('invoice_date')
                            ->label('تاریخ فاکتور')
                            ->jalaliDate(),

                        // «۹» → «۹٪»؛ cast مقدار را decimal:2 نگه می‌دارد، پس
                        // ابتدا به float تبدیل می‌شود تا 9.00 به‌صورت 9 دیده شود.
                        TextEntry::make('vat_rate')
                            ->label('نرخ ارزش افزوده (٪)')
                            ->formatStateUsing(fn ($state): ?string => filled($state)
                                ? sprintf('%s%%', (float) $state)
                                : null)
                            ->placeholder('—'),
                    ]),

                // ── کالا و خدمات (عنوان پویا، مثل فرم) ──
                Section::make(fn ($record): string => 'کالا و خدمات (' . (self::CURRENCIES[$record->currency] ?? '') . ')')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('اقلام')
                            ->schema([
                                TextEntry::make('item_code')
                                    ->label('کد کالا')
                                    ->placeholder('—'),

                                TextEntry::make('description')
                                    ->label('شرح کالا و خدمات'),

                                TextEntry::make('quantity')
                                    ->label('تعداد'),

                                TextEntry::make('unit_price')
                                    ->label('قیمت واحد')
                                    ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),

                                TextEntry::make('net_sales')
                                    ->label('فروش')
                                    ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),
                            ])
                            ->columns(5),

                        Section::make('جمع کل فاکتور')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('subtotal')
                                    ->label('جمع کل فروش')
                                    ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),

                                TextEntry::make('vat_amount')
                                    ->label('جمع مالیات و عوارض')
                                    ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),

                                TextEntry::make('grand_total')
                                    ->label('مبلغ کل فاکتور')
                                    ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ',')
                                    ->weight('bold'),
                            ]),

                        Section::make('توضیحات')
                            ->schema([
                                TextEntry::make('notes')
                                    ->hiddenLabel()
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
