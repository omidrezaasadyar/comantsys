<?php

namespace App\Filament\Resources\Invoices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Morilog\Jalali\Jalalian;
use App\Services\InvoicePdfService;
use Filament\Actions\Action;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('شماره')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('items.description')
                    ->label('کالا و خدمات')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->toggleable(),

                TextColumn::make('type')
                    ->label('نوع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'invoice'  => 'فاکتور فروش',
                        'proforma' => 'پیش‌فاکتور',
                        default    => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'invoice' ? 'success' : 'warning'),

                TextColumn::make('customer.name')
                    ->label('درخواست‌کننده')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company.name')
                    ->label('فروشنده')
                    ->toggleable(),

                TextColumn::make('grand_total')
                    ->label('مبلغ کل')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

                TextColumn::make('invoice_date')
                    ->label('تاریخ فاکتور')
                    ->formatStateUsing(fn ($state) => $state
                        ? Jalalian::fromDateTime($state)->format('Y/m/d')
                        : '-')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('نوع')
                    ->options([
                        'invoice'  => 'فاکتور فروش',
                        'proforma' => 'پیش‌فاکتور',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($record) {
                        $content = InvoicePdfService::generate($record);
                        $filename = ($record->invoice_number ?: 'invoice') . '.pdf';

                        return response()->streamDownload(
                            fn () => print($content),
                            $filename,
                            ['Content-Type' => 'application/pdf']
                        );
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
