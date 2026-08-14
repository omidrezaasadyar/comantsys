<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Filament\Actions\DeleteSelectedBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesTable
{
    /**
     * Column visibility rules (Filament v5, Columns\Concerns\CanBeToggled):
     *
     *  - `$isToggleable` defaults to FALSE, so a column with no ->toggleable()
     *    call is LOCKED: the column manager lists it checked and disabled, and
     *    the user cannot switch it off (HasColumnManager.php:237-239 →
     *    'isToggled' => true, 'isToggleable' => false).
     *  - ->toggleable() is toggleable(true, isToggledHiddenByDefault: false),
     *    i.e. hideable but still shown by default.
     *  - ->toggleable(isToggledHiddenByDefault: true) is hideable and hidden
     *    until the user turns it on.
     *
     * Locked here: نام قلم · مشتری · تاریخ فروش · سود.
     * Column order and default visibility are unchanged.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sale_date', 'desc')
            ->columns([
                // locked
                TextColumn::make('item_name')
                    ->label('نام قلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->label('تأمین‌کننده')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                // locked
                TextColumn::make('customer_name')
                    ->label('مشتری')
                    ->searchable()
                    ->placeholder('—'),

                // locked
                TextColumn::make('sale_date')
                    ->label('تاریخ فروش')
                    ->date()
                    ->sortable(),

                TextColumn::make('currency')
                    ->label('ارز')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->label('تعداد')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('purchase_unit_price')
                    ->label('خرید واحد')
                    ->money(fn ($record) => $record->currency)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('sale_unit_price')
                    ->label('فروش واحد')
                    ->money(fn ($record) => $record->currency)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('revenue')
                    ->label('درآمد')
                    ->money(fn ($record) => $record->currency)
                    ->color('warning')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_cost')
                    ->label('هزینهٔ کل')
                    ->money(fn ($record) => $record->currency)
                    ->color('danger')
                    ->sortable()
                    ->toggleable(),

                // locked
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
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteSelectedBulkAction::make(),
            ]);
    }
}
