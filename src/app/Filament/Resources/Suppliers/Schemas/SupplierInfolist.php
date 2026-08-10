<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات اصلی')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('نام تأمین‌کننده')->columnSpanFull(),
                        // TextEntry has no boolean() in this version (IconEntry only),
                        // so the flag is rendered as a formatted badge instead.
                        TextEntry::make('is_active')
                            ->label('فعال')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'بله' : 'خیر')
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    ]),
                Section::make('موقعیت مکانی')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('country')->label('کشور'),
                        TextEntry::make('city')->label('شهر'),
                        TextEntry::make('address')->label('نشانی')->columnSpanFull(),
                    ]),
                Section::make('راه‌های ارتباطی')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('phone')->label('تلفن'),
                        TextEntry::make('email')->label('ایمیل'),
                        TextEntry::make('website')->label('وب‌سایت')->columnSpanFull(),
                    ]),
                Section::make('اطلاعات تکمیلی')
                    ->schema([
                        TextEntry::make('tags')->label('برچسب‌ها'),
                        TextEntry::make('notes')->label('یادداشت‌ها')->columnSpanFull(),
                    ]),
            ]);
    }
}
