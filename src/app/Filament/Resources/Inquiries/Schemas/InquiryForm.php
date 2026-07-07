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
                        // ردیف ۱: مشتری | شمارهٔ استعلام | تاریخ استعلام | تاریخ پاسخ | وضعیت
                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 5])
                            ->schema([
                                Select::make('customer_id')
                                    ->label(__('inquiries.field.customer'))
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                TextInput::make('inquiry_number')
                                    ->label(__('inquiries.field.inquiry_number'))
                                    ->helperText(__('inquiries.help.inquiry_number'))
                                    ->required()
                                    ->unique(ignoreRecord: true),

                                // انتخابگر تاریخ شمسی (ariaieboy/filament-jalali) — ذخیره به میلادی
                                DatePicker::make('inquiry_date')
                                    ->label(__('inquiries.field.inquiry_date'))
                                    ->jalali()
                                    ->required(),

                                // انتخابگر تاریخ شمسی (اختیاری) — ذخیره به میلادی
                                DatePicker::make('response_date')
                                    ->label(__('inquiries.field.response_date'))
                                    ->jalali(),

                                Select::make('status')
                                    ->label(__('inquiries.field.status'))
                                    ->options(Inquiry::statuses())
                                    ->default('received')
                                    ->required()
                                    ->native(false),
                            ]),

                        // ردیف ۲: توضیحات (تمام‌عرض)
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
