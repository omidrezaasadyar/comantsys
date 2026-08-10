<?php

namespace App\Filament\Resources\Inquiries\Schemas;

use App\Models\Inquiry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ── اطلاعات استعلام ──
                Section::make(__('inquiries.section.info'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('customer.name')
                            ->label(__('inquiries.field.customer')),

                        TextEntry::make('inquiry_number')
                            ->label(__('inquiries.field.inquiry_number')),

                        TextEntry::make('inquiry_date')
                            ->label(__('inquiries.field.inquiry_date'))
                            ->jalaliDate(),

                        TextEntry::make('response_date')
                            ->label(__('inquiries.field.response_date'))
                            ->jalaliDate(),

                        TextEntry::make('status')
                            ->label(__('inquiries.field.status'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => Inquiry::statuses()[$state] ?? $state)
                            ->color(fn ($state) => Inquiry::statusColors()[$state] ?? 'gray'),

                        TextEntry::make('direction')
                            ->label(__('inquiries.field.direction'))
                            ->formatStateUsing(fn (?string $state) => $state ? __('inquiries.direction.'.$state) : '—'),

                        TextEntry::make('company.name')
                            ->label(__('inquiries.field.company'))
                            ->placeholder('—'),

                        TextEntry::make('description')
                            ->label(__('inquiries.field.description'))
                            ->columnSpanFull(),
                    ]),

                // ── اقلام استعلام ──
                Section::make(__('inquiries.section.items'))
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('description')
                                    ->label(__('inquiries.field.item_description')),

                                TextEntry::make('quantity')
                                    ->label(__('inquiries.field.quantity')),

                                // از اکسسور getUnitLabelAttribute مدل — در حالت «سایر» مقدار unit_other را برمی‌گرداند
                                TextEntry::make('unit_label')
                                    ->label(__('inquiries.field.unit')),

                                TextEntry::make('notes')
                                    ->label(__('inquiries.field.item_notes'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
}
