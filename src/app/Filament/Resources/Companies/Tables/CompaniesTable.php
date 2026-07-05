<?php

namespace App\Filament\Resources\Companies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('لوگو')
                    ->circular(),

                TextColumn::make('name')
                    ->label('نام شرکت')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('prefix')
                    ->label('پیشوند')
                    ->badge()
                    ->color('info'),

                TextColumn::make('locale')
                    ->label('زبان')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'fa' => 'فارسی',
                        'en' => 'انگلیسی',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'fa' ? 'success' : 'warning'),

                TextColumn::make('default_currency')
                    ->label('ارز پیش‌فرض')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'IRR' => 'ریال',
                        'EUR' => 'یورو',
                        'USD' => 'دلار',
                        'GBP' => 'پوند',
                        default => $state,
                    }),

                TextColumn::make('phone')
                    ->label('تلفن')
                    ->toggleable(),

                TextColumn::make('national_id')
                    ->label('شناسه ملی')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->dateTime('Y/m/d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function ($record, $action) {
                        if ($record->invoices()->withTrashed()->exists()) {
                            Notification::make()
                                ->title('امکان حذف وجود ندارد')
                                ->body('این شرکت دارای فاکتور است و قابل حذف نیست. ابتدا فاکتورهای مرتبط را مدیریت کنید.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (Collection $records, $action) {
                            $withInvoices = $records->filter(
                                fn ($record) => $record->invoices()->withTrashed()->exists()
                            );

                            if ($withInvoices->isNotEmpty()) {
                                Notification::make()
                                    ->title('امکان حذف وجود ندارد')
                                    ->body('برخی از شرکت‌های انتخاب‌شده دارای فاکتور هستند و قابل حذف نیستند: ' . $withInvoices->pluck('name')->implode('، '))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }
}
