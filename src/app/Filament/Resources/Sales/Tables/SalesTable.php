<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sale_date', 'desc')
            ->columns([
                TextColumn::make('item_name')
                    ->label('نام قلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->label('تأمین‌کننده')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('customer_name')
                    ->label('مشتری')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('sale_date')
                    ->label('تاریخ فروش')
                    ->date()
                    ->sortable(),

                TextColumn::make('currency')
                    ->label('ارز')
                    ->badge()
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label('تعداد')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('purchase_unit_price')
                    ->label('خرید واحد')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

                TextColumn::make('sale_unit_price')
                    ->label('فروش واحد')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

                TextColumn::make('revenue')
                    ->label('درآمد')
                    ->money(fn ($record) => $record->currency)
                    ->color('warning')
                    ->sortable(),

                TextColumn::make('total_cost')
                    ->label('هزینهٔ کل')
                    ->money(fn ($record) => $record->currency)
                    ->color('danger')
                    ->sortable(),

                TextColumn::make('profit')
                    ->label('سود')
                    ->money(fn ($record) => $record->currency)
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),

                // ستون‌های کمکی — پیش‌فرض مخفی، با منوی ستون‌ها قابل‌نمایش
                TextColumn::make('total_purchase')
                    ->label('خرید کل')
                    ->money(fn ($record) => $record->currency)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('extra_costs_total')
                    ->label('هزینه‌های جانبی')
                    ->money(fn ($record) => $record->currency)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('آخرین بروزرسانی')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
