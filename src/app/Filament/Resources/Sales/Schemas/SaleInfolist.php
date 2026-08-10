<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SaleInfolist
{
    /**
     * همان نگاشت ارز که در SaleForm برای Select::make('currency') استفاده شده —
     * منبع یگانه برای برچسب ارز در نما.
     *
     * @var array<string, string>
     */
    protected const CURRENCIES = [
        'IRR' => 'ریال (IRR)',
        'EUR' => 'یورو (EUR)',
        'GBP' => 'پوند (GBP)',
        'USD' => 'دلار (USD)',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ── اطلاعات اصلی + مبالغ (هم‌ترتیب با SaleForm) ──
                Section::make('اطلاعات اصلی')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('item_name')
                            ->label('نام قلم فروش‌رفته'),

                        TextEntry::make('supplier.name')
                            ->label('تأمین‌کننده')
                            ->placeholder('—'),

                        TextEntry::make('customer_name')
                            ->label('نام مشتری')
                            ->placeholder('—'),

                        TextEntry::make('sale_date')
                            ->label('تاریخ فروش')
                            ->jalaliDate(),

                        TextEntry::make('currency')
                            ->label('ارز')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): ?string => $state
                                ? (self::CURRENCIES[$state] ?? $state)
                                : null),

                        // نرخ‌های ارز فقط وقتی معامله ریالی نیست — همان شرط فرم
                        TextEntry::make('exchange_rate_ice')
                            ->label('نرخ ارز ICE (به ریال)')
                            ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ',')
                            ->placeholder('—')
                            ->visible(fn ($record) => $record->currency !== 'IRR'),

                        TextEntry::make('exchange_rate_free')
                            ->label('نرخ ارز آزاد (به ریال)')
                            ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ',')
                            ->placeholder('—')
                            ->visible(fn ($record) => $record->currency !== 'IRR'),

                        TextEntry::make('quantity')
                            ->label('تعداد'),

                        TextEntry::make('purchase_unit_price')
                            ->label('قیمت خرید واحد')
                            ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),

                        TextEntry::make('sale_unit_price')
                            ->label('قیمت فروش واحد')
                            ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),

                        TextEntry::make('notes')
                            ->label('یادداشت')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),

                // ── هزینه‌های جانبی ──
                Section::make('هزینه‌های جانبی')
                    ->description('حمل‌ونقل، گمرک، ایاب‌وذهاب و سایر هزینه‌های مقطوعِ این معامله')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('costs')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('title')
                                    ->label('عنوان هزینه'),

                                TextEntry::make('amount')
                                    ->label('مبلغ')
                                    ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),
                            ])
                            ->columns(2),
                    ]),

                // ── نتیجهٔ محاسبه (فیلدهای کمکیِ مخفیِ فرم عمداً نیامده‌اند) ──
                Section::make('نتیجهٔ محاسبه')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('revenue')
                            ->label('درآمد')
                            ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),

                        TextEntry::make('total_cost')
                            ->label('هزینهٔ کل')
                            ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),

                        TextEntry::make('profit')
                            ->label('سود')
                            ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ',')
                            ->weight('bold')
                            ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                    ]),
            ]);
    }
}
