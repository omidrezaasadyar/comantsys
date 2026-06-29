<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\Sale;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SaleForm
{
    /**
     * منطق محاسبهٔ زنده: مقادیر فعلی فرم را می‌خواند، از روی مدل بازمحاسبه
     * می‌کند و فیلدهای نتیجه را پر می‌کند. منبع یگانهٔ حقیقت = Sale::calculateFinancials.
     */
    protected static function recalculate(Get $get, Set $set): void
    {
        $financials = Sale::calculateFinancials(
            (float) ($get('quantity') ?? 0),
            (float) ($get('purchase_unit_price') ?? 0),
            (float) ($get('sale_unit_price') ?? 0),
            $get('costs') ?? []
        );

        $set('total_purchase', number_format($financials['total_purchase'], 2, '.', ''));
        $set('extra_costs_total', number_format($financials['extra_costs_total'], 2, '.', ''));
        $set('total_cost', number_format($financials['total_cost'], 2, '.', ''));
        $set('revenue', number_format($financials['revenue'], 2, '.', ''));
        $set('profit', number_format($financials['profit'], 2, '.', ''));
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ── ردیف ۱: اطلاعات اصلی + مبالغ (یک کارت تمام‌عرض) ──
                Section::make('اطلاعات اصلی')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('item_name')
                            ->label('نام قلم فروش‌رفته')
                            ->required(),

                        Select::make('supplier_id')
                            ->label('تأمین‌کننده')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('انتخاب کنید (اختیاری)'),

                        TextInput::make('customer_name')
                            ->label('نام مشتری'),

                        DatePicker::make('sale_date')
                            ->label('تاریخ فروش')
                            ->native(false)
                            ->format('Y-m-d')
                            ->displayFormat('Y/m/d')
                            ->dehydrated(true)
                            ->live(false),

                        Select::make('currency')
                            ->label('ارز')
                            ->options([
                                'IRR' => 'ریال (IRR)',
                                'EUR' => 'یورو (EUR)',
                                'GBP' => 'پوند (GBP)',
                                'USD' => 'دلار (USD)',
                            ])
                            ->default('IRR')
                            ->required()
                            ->live(),

                        TextInput::make('exchange_rate_ice')
                            ->label('نرخ ارز ICE (به ریال)')
                            ->numeric()
                            ->placeholder('نرخ رسمی در زمان معامله')
                            ->visible(fn (Get $get) => $get('currency') !== 'IRR'),

                        TextInput::make('exchange_rate_free')
                            ->label('نرخ ارز آزاد (به ریال)')
                            ->numeric()
                            ->placeholder('نرخ بازار آزاد در زمان معامله')
                            ->visible(fn (Get $get) => $get('currency') !== 'IRR'),

                        // مبالغ — داخل همین کارت
                        TextInput::make('quantity')
                            ->label('تعداد')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculate($get, $set)),

                        TextInput::make('purchase_unit_price')
                            ->label('قیمت خرید واحد')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculate($get, $set)),

                        TextInput::make('sale_unit_price')
                            ->label('قیمت فروش واحد')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculate($get, $set)),

                        Textarea::make('notes')
                            ->label('یادداشت')
                            ->columnSpanFull(),
                    ]),

                // ── ردیف ۲: هزینه‌های جانبی (تمام‌عرض، شبکهٔ ۴ ستونه) ──
                Section::make('هزینه‌های جانبی')
                    ->description('حمل‌ونقل، گمرک، ایاب‌وذهاب و سایر هزینه‌های مقطوعِ این معامله')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('costs')
                            ->hiddenLabel()
                            ->relationship('costs')
                            ->grid(4)
                            ->schema([
                                TextInput::make('title')
                                    ->label('عنوان هزینه')
                                    ->required(),

                                TextInput::make('amount')
                                    ->label('مبلغ')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                            ])
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculate($get, $set))
                            ->deleteAction(
                                fn ($action) => $action->after(fn (Get $get, Set $set) => static::recalculate($get, $set))
                            )
                            ->addActionLabel('افزودن هزینه')
                            ->defaultItems(0),
                    ]),

                // ── ردیف ۳: نتیجهٔ محاسبه (تمام‌عرض، ۳ ستونه) ──
                Section::make('نتیجهٔ محاسبه')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('revenue')
                            ->label('درآمد')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->default(0),

                        TextInput::make('total_cost')
                            ->label('هزینهٔ کل')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->default(0),

                        TextInput::make('profit')
                            ->label('سود')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->default(0),

                        TextInput::make('total_purchase')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->default(0)
                            ->hidden(),

                        TextInput::make('extra_costs_total')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->default(0)
                            ->hidden(),
                    ]),
            ]);
    }
}
