<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات اصلی')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('person_type')
                            ->label('نوع شخص')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'legal' => 'حقوقی',
                                'real'  => 'حقیقی',
                                default => '—',
                            }),
                        TextEntry::make('name')->label('نام / شرکت'),
                        TextEntry::make('national_id')->label('شناسه ملی / کد ملی'),
                        TextEntry::make('economic_code')->label('کد اقتصادی'),
                    ]),
                Section::make('اطلاعات تماس')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('phone')->label('تلفن'),
                        TextEntry::make('postal_code')->label('کد پستی'),
                        TextEntry::make('address')->label('آدرس')->columnSpanFull(),
                    ]),
                Section::make('سایر')
                    ->schema([
                        TextEntry::make('notes')->label('یادداشت')->columnSpanFull(),
                    ]),
            ]);
    }
}
