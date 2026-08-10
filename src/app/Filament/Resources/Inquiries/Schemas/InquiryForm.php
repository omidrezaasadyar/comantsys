<?php

namespace App\Filament\Resources\Inquiries\Schemas;

use App\Models\Inquiry;
use App\Models\InquiryItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class InquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ── اطلاعات استعلام ──
                Section::make(__('inquiries.section.info'))
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        // ردیف ۱: نوع استعلام | وضعیت | شمارهٔ استعلام
                        Grid::make(3)
                            ->schema([
                                // جهت استعلام — تعیین می‌کند شرکتِ استعلام‌دهنده لازم است یا نه
                                Select::make('direction')
                                    ->label(__('inquiries.field.direction'))
                                    ->options(__('inquiries.direction'))
                                    ->default('inbound')
                                    ->required()
                                    ->live()
                                    ->native(false),

                                Select::make('status')
                                    ->label(__('inquiries.field.status'))
                                    ->options(Inquiry::statuses())
                                    ->default('received')
                                    ->required()
                                    ->native(false),

                                TextInput::make('inquiry_number')
                                    ->label(__('inquiries.field.inquiry_number'))
                                    ->helperText(__('inquiries.help.inquiry_number'))
                                    ->required()
                                    ->unique(ignoreRecord: true),
                            ]),

                        // شرکت استعلام‌دهنده — ردیف مستقل تا ردیف ۱ را در حالت «دریافتی» به‌هم نریزد
                        Grid::make(3)
                            ->schema([
                                Select::make('company_id')
                                    ->label(__('inquiries.field.company'))
                                    ->relationship('company', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->visible(fn (Get $get) => $get('direction') === 'outbound')
                                    ->required(fn (Get $get) => $get('direction') === 'outbound'),
                            ]),

                        // ردیف ۲: تقویم | تاریخ استعلام | تاریخ پاسخ | مشتری
                        // هر تاریخ دو انتخابگرِ موازی دارد که فقط یکی‌شان دیده می‌شود
                        Grid::make(4)
                            ->schema([
                                // انتخاب دستی تقویم — ذخیرهٔ تاریخ‌ها همیشه میلادی است
                                Select::make('calendar')
                                    ->label('تقویم')
                                    ->options([
                                        'jalali'    => 'شمسی',
                                        'gregorian' => 'میلادی',
                                    ])
                                    ->default('jalali')
                                    ->required()
                                    ->live()
                                    ->native(false),

                                // جفتِ تاریخ استعلام — یک ستون از گرید، فقط یکی از دو انتخابگر دیده می‌شود
                                Group::make([
                                    // شمسی (ariaieboy/filament-jalali) — ذخیره به میلادی
                                    DatePicker::make('inquiry_date')
                                        ->label(__('inquiries.field.inquiry_date'))
                                        ->jalali()
                                        ->required(fn (Get $get) => $get('calendar') === 'jalali')
                                        ->visible(fn (Get $get) => $get('calendar') === 'jalali'),

                                    // میلادی
                                    DatePicker::make('inquiry_date')
                                        ->label(__('inquiries.field.inquiry_date'))
                                        ->native(false)
                                        ->displayFormat('Y/m/d')
                                        ->required(fn (Get $get) => $get('calendar') === 'gregorian')
                                        ->visible(fn (Get $get) => $get('calendar') === 'gregorian'),
                                ])->columns(1),

                                // جفتِ تاریخ پاسخ (اختیاری) — یک ستون از گرید
                                Group::make([
                                    // شمسی
                                    DatePicker::make('response_date')
                                        ->label(__('inquiries.field.response_date'))
                                        ->jalali()
                                        ->visible(fn (Get $get) => $get('calendar') === 'jalali'),

                                    // میلادی
                                    DatePicker::make('response_date')
                                        ->label(__('inquiries.field.response_date'))
                                        ->native(false)
                                        ->displayFormat('Y/m/d')
                                        ->visible(fn (Get $get) => $get('calendar') === 'gregorian'),
                                ])->columns(1),

                                Select::make('customer_id')
                                    ->label(__('inquiries.field.customer'))
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),

                        // ردیف ۳: توضیحات (تمام‌عرض)
                        Textarea::make('description')
                            ->label(__('inquiries.field.description'))
                            ->columnSpanFull(),
                    ]),

                // ── اقلام استعلام (Repeater قابل مرتب‌سازی و جمع‌شونده) ──
                Section::make(__('inquiries.section.items'))
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->relationship('items')
                            ->schema([
                                // ردیف ۱: شرح قلم | تعداد | واحد
                                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])
                                    ->schema([
                                        TextInput::make('description')
                                            ->label(__('inquiries.field.item_description'))
                                            ->required()
                                            ->columnSpan(['lg' => 2]),

                                        TextInput::make('quantity')
                                            ->label(__('inquiries.field.quantity'))
                                            ->numeric()
                                            ->required(),

                                        Select::make('unit')
                                            ->label(__('inquiries.field.unit'))
                                            ->options(InquiryItem::units())
                                            ->native(false)
                                            ->required()
                                            ->live(),
                                    ]),

                                // فقط وقتی «سایر» انتخاب شود ظاهر می‌شود — ردیف مستقل
                                TextInput::make('unit_other')
                                    ->label(__('inquiries.field.unit_other'))
                                    ->visible(fn (Get $get): bool => $get('unit') === 'other')
                                    ->requiredIf('unit', 'other')
                                    ->columnSpanFull(),

                                // ردیف آخر: یادداشت (تمام‌عرض)
                                Textarea::make('notes')
                                    ->label(__('inquiries.field.item_notes'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                            ->addActionLabel(__('inquiries.add_item'))
                            ->defaultItems(1),
                    ]),
            ]);
    }
}
