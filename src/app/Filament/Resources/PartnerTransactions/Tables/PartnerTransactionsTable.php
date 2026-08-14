<?php

namespace App\Filament\Resources\PartnerTransactions\Tables;

use App\Filament\Actions\DeleteSelectedBulkAction;
use App\Models\PartnerTransaction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PartnerTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('txn_date', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('طرف حساب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('نوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PartnerTransaction::types()[$state] ?? $state)
                    ->color(fn (string $state): string => PartnerTransaction::typeColors()[$state] ?? 'gray'),

                TextColumn::make('amount')
                    ->label('مبلغ')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PartnerTransaction::statuses()[$state] ?? $state)
                    ->color(fn (string $state): string => PartnerTransaction::statusColors()[$state] ?? 'gray'),

                TextColumn::make('payment_method')
                    ->label('روش پرداخت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (PartnerTransaction::paymentMethods()[$state] ?? $state) : '—')
                    ->color(fn (?string $state): string => match ($state) {
                        'bank' => 'info',
                        'cash' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('txn_date')
                    ->label('تاریخ پرداخت')
                    ->jalaliDate()
                    ->sortable(),

                TextColumn::make('purpose')
                    ->label('بابت')
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('account_holder')
                    ->label('صاحب حساب مقصد')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('account_number')
                    ->label('شماره حساب مقصد')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('creator.name')
                    ->label('ثبت‌کننده')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('زمان ثبت')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('طرف حساب')
                    ->relationship('user', 'name', fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'partner')))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('type')
                    ->label('نوع')
                    ->options(PartnerTransaction::types()),

                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(PartnerTransaction::statuses()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteSelectedBulkAction::make(),
            ]);
    }
}
