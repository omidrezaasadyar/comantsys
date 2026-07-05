<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات فاکتور')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        Select::make('locale')
                            ->label('زبان فاکتور')
                            ->options([
                                'fa' => 'فارسی (تاریخ شمسی)',
                                'en' => 'انگلیسی (تاریخ میلادی)',
                            ])
                            ->default('fa')
                            ->required()
                            ->live()
                            ->native(false),

                        Select::make('type')
                            ->label('نوع')
                            ->options([
                                'proforma' => 'پیش‌فاکتور',
                                'invoice'  => 'فاکتور فروش',
                            ])
                            ->default('proforma')
                            ->required()
                            ->native(false),

                        Select::make('company_id')
                            ->label('شرکت فروشنده')
                            ->relationship('company', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $company = \App\Models\Company::find($state);
                                    if ($company) {
                                        $set('locale', $company->locale);
                                    }
                                }
                            }),

                        Select::make('customer_id')
                            ->label('درخواست‌کننده (خریدار)')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('نام / شرکت')
                                    ->required(),
                                TextInput::make('national_id')->label('شناسه ملی'),
                                TextInput::make('phone')->label('تلفن'),
                            ]),

                        Select::make('currency')
                            ->label('ارز')
                            ->options([
                                'IRR' => 'ریال',
                                'EUR' => 'یورو',
                                'USD' => 'دلار',
                                'GBP' => 'پوند',
                            ])
                            ->default('IRR')
                            ->required()
                            ->live()
                            ->native(false),

                        TextInput::make('vat_rate')
                            ->label('نرخ ارزش افزوده (٪)')
                            ->numeric()
                            ->default(10)
                            ->suffix('٪')
                            ->live(debounce: 500)
                            ->visible(fn ($get) => $get('currency') === 'IRR'),

                        TextInput::make('expert_name')
                            ->label('نام کارشناس'),

                        TextInput::make('inquiry_number')
                            ->label('شماره استعلام'),

                        // تاریخ فاکتور شمسی (فقط locale=fa)
                        TextInput::make('invoice_date_jalali')
                            ->label('تاریخ فاکتور (شمسی)')
                            ->placeholder('1405/04/09')
                            ->mask('9999/99/99')
                            ->required()
                            ->default(fn () => self::gregorianToJalali(now()))
                            ->visible(fn ($get) => $get('locale') === 'fa'),

                        // تاریخ استعلام شمسی (فقط locale=fa)
                        TextInput::make('inquiry_date_jalali')
                            ->label('تاریخ استعلام (شمسی)')
                            ->placeholder('1405/04/09')
                            ->mask('9999/99/99')
                            ->visible(fn ($get) => $get('locale') === 'fa'),

                        // تاریخ فاکتور میلادی (فقط locale=en)
                        DatePicker::make('invoice_date')
                            ->label('تاریخ فاکتور (میلادی)')
                            ->native(false)
                            ->displayFormat('Y/m/d')
                            ->default(now())
                            ->live()
                            ->required(fn ($get) => $get('locale') === 'en')
                            ->visible(fn ($get) => $get('locale') === 'en'),

                        // تاریخ استعلام میلادی (فقط locale=en)
                        DatePicker::make('inquiry_date')
                            ->label('تاریخ استعلام (میلادی)')
                            ->native(false)
                            ->displayFormat('Y/m/d')
                            ->visible(fn ($get) => $get('locale') === 'en'),
                    ]),

                Section::make(fn ($get) => 'کالا و خدمات (' . self::currencyLabel($get('currency')) . ')')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->label('اقلام')
                            ->relationship('items')
                            ->defaultItems(1)
                            ->addActionLabel('افزودن ردیف')
                            ->live()
                            ->table([
                                TableColumn::make('کد کالا'),
                                TableColumn::make('شرح کالا و خدمات')->markAsRequired(),
                                TableColumn::make('تعداد')->markAsRequired(),
                                TableColumn::make('قیمت واحد')->markAsRequired(),
                                TableColumn::make('فروش'),
                                TableColumn::make('مالیات و عوارض'),
                                TableColumn::make('مبلغ کل'),
                            ])
                            ->schema([
                                TextInput::make('item_code')
                                    ->extraInputAttributes(['style' => 'max-width: 80px']),

                                TextInput::make('description')
                                    ->required()
                                    ->extraInputAttributes(['style' => 'min-width: 280px']),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->extraInputAttributes(['style' => 'max-width: 55px']),

                                TextInput::make('unit_price')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->extraInputAttributes(['style' => 'max-width: 160px']),

                                Placeholder::make('row_net')
                                    ->content(fn ($get) => self::fmtNumber(
                                        (float) $get('quantity') * (float) $get('unit_price')
                                    )),

                                Placeholder::make('row_vat')
                                    ->content(function ($get) {
                                        $net = (float) $get('quantity') * (float) $get('unit_price');
                                        $rate = (float) ($get('../../vat_rate') ?? 0);
                                        $vat = $get('../../currency') === 'IRR' ? round($net * $rate / 100) : 0;
                                        return self::fmtNumber($vat);
                                    }),

                                Placeholder::make('row_total')
                                    ->content(function ($get) {
                                        $net = (float) $get('quantity') * (float) $get('unit_price');
                                        $rate = (float) ($get('../../vat_rate') ?? 0);
                                        $vat = $get('../../currency') === 'IRR' ? round($net * $rate / 100) : 0;
                                        return self::fmtNumber($net + $vat);
                                    }),
                            ]),

                        Section::make('جمع کل فاکتور')
                            ->schema([
                                Placeholder::make('totals_box')
                                    ->hiddenLabel()
                                    ->content(function ($get) {
                                        $currency = $get('currency');
                                        $rate = (float) ($get('vat_rate') ?? 0);
                                        $isIRR = $currency === 'IRR';

                                        $net = 0;
                                        $vat = 0;
                                        foreach ($get('items') ?? [] as $item) {
                                            $rowNet = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
                                            $net += $rowNet;
                                            $vat += $isIRR ? round($rowNet * $rate / 100) : 0;
                                        }
                                        $total = $net + $vat;

                                        $rows = [
                                            ['جمع کل فروش', $net],
                                            ['جمع مالیات و عوارض', $vat],
                                            ['مبلغ کل فاکتور', $total],
                                        ];

                                        $html = '<div style="display:flex; flex-direction:column; gap:8px; font-size:14px;">';
                                        foreach ($rows as $i => [$label, $value]) {
                                            $isLast = $i === count($rows) - 1;
                                            $weight = $isLast ? 'font-weight:700; font-size:16px;' : 'font-weight:400;';
                                            $border = $isLast ? 'border-top:1px solid var(--gray-300, #d1d5db); padding-top:8px; margin-top:4px;' : '';
                                            $html .= '<div style="display:flex; justify-content:space-between; ' . $weight . $border . '">'
                                                . '<span>' . $label . ' :</span>'
                                                . '<span>' . self::fmtMoney($value, $currency) . '</span>'
                                                . '</div>';
                                        }
                                        $html .= '</div>';

                                        return new \Illuminate\Support\HtmlString($html);
                                    }),
                            ]),

                        Section::make('توضیحات')
                            ->schema([
                                Textarea::make('notes')
                                    ->hiddenLabel()
                                    ->rows(6)
                                    ->placeholder("۱ - \n۲ - \n۳ - ")
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    protected static function fmtMoney(float $value, ?string $currency): string
    {
        $suffix = self::currencyLabel($currency);

        return number_format($value) . ' ' . $suffix;
    }

    protected static function currencyLabel(?string $currency): string
    {
        return [
            'IRR' => 'ریال',
            'EUR' => 'یورو',
            'USD' => 'دلار',
            'GBP' => 'پوند',
        ][$currency] ?? '';
    }

    protected static function fmtNumber(float $value): string
    {
        return number_format($value);
    }

    protected static function sumNet($get): float
    {
        $sum = 0;
        foreach ($get('items') ?? [] as $item) {
            $sum += (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
        }
        return $sum;
    }

    /**
     * شمسی (1405/04/09) → میلادی (Y-m-d) برای ذخیره در دیتابیس.
     */
    public static function jalaliToGregorian(?string $jalali): ?string
    {
        if (empty($jalali)) {
            return null;
        }

        $jalali = self::toEnglishDigits(trim($jalali));

        try {
            return \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $jalali)
                ->toCarbon()
                ->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected static function toEnglishDigits(string $string): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace([...$persian, ...$arabic], [...$english, ...$english], $string);
    }

    /**
     * میلادی (دیتابیس) → شمسی (1405/04/09) برای نمایش در فرم.
     */
    public static function gregorianToJalali($gregorian): ?string
    {
        if (empty($gregorian)) {
            return null;
        }

        try {
            $j = \Morilog\Jalali\Jalalian::fromDateTime($gregorian);
            return sprintf('%d/%02d/%02d', $j->getYear(), $j->getMonth(), $j->getDay());
        } catch (\Throwable $e) {
            return null;
        }
    }
}