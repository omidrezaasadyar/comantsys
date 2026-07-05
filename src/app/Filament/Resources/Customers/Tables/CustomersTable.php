<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('نام / شرکت')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('person_type')
                    ->label('نوع شخص')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'legal' => 'حقوقی',
                        'real'  => 'حقیقی',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'legal' ? 'info' : 'gray'),

                TextColumn::make('national_id')
                    ->label('شناسه ملی')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('economic_code')
                    ->label('کد اقتصادی')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('تلفن')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->dateTime('Y/m/d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('person_type')
                    ->label('نوع شخص')
                    ->options([
                        'legal' => 'حقوقی',
                        'real'  => 'حقیقی',
                    ]),
            ])
            ->defaultSort('name')
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
