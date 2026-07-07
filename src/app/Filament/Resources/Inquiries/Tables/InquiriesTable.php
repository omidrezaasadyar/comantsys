<?php

namespace App\Filament\Resources\Inquiries\Tables;

use App\Models\Inquiry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('inquiry_date', 'desc')
            ->columns([
                TextColumn::make('inquiry_number')
                    ->label(__('inquiries.field.inquiry_number'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('customer.name')
                    ->label(__('inquiries.field.customer'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('inquiry_date')
                    ->label(__('inquiries.field.inquiry_date'))
                    ->jalaliDate()
                    ->sortable(),

                TextColumn::make('response_date')
                    ->label(__('inquiries.field.response_date'))
                    ->jalaliDate()
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('inquiries.field.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Inquiry::statuses()[$state] ?? $state)
                    ->color(fn (string $state): string => Inquiry::statusColors()[$state] ?? 'gray')
                    ->sortable(),

                // جست‌وجو روی شرح اقلام (رابطهٔ items.description)
                TextColumn::make('items.description')
                    ->label(__('inquiries.field.items'))
                    ->badge()
                    ->separator(',')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('inquiries.field.created_at'))
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('inquiries.field.updated_at'))
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('inquiries.field.status'))
                    ->options(Inquiry::statuses()),
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
