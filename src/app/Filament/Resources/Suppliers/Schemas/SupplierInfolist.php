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
                    ->contained(false)
                    ->divided()
                    ->inlineLabel()
                    ->schema([
                        TextEntry::make('name')->label('نام تأمین‌کننده')->placeholder('—')->columnSpanFull(),
                        // TextEntry has no boolean() in this version (IconEntry only),
                        // so the flag is rendered as a formatted badge instead.
                        TextEntry::make('is_active')
                            ->label('فعال')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'بله' : 'خیر')
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    ]),
                Section::make('موقعیت مکانی')
                    ->contained(false)
                    ->divided()
                    ->inlineLabel()
                    ->schema([
                        TextEntry::make('country')->label('کشور')->placeholder('—'),
                        TextEntry::make('city')->label('شهر')->placeholder('—'),
                        TextEntry::make('address')
                            ->label('نشانی')
                            ->placeholder('—')
                            ->inlineLabel(false)
                            ->columnSpanFull(),
                    ]),
                Section::make('راه‌های ارتباطی')
                    ->contained(false)
                    ->divided()
                    ->inlineLabel()
                    ->schema([
                        TextEntry::make('phone')->label('تلفن')->placeholder('—'),
                        TextEntry::make('email')->label('ایمیل')->placeholder('—'),
                        TextEntry::make('website')->label('وب‌سایت')->placeholder('—'),
                    ]),
                Section::make('اطلاعات تکمیلی')
                    ->contained(false)
                    ->divided()
                    ->inlineLabel()
                    ->schema([
                        TextEntry::make('tags')->label('برچسب‌ها')->placeholder('—'),
                        TextEntry::make('notes')
                            ->label('یادداشت‌ها')
                            ->placeholder('—')
                            ->inlineLabel(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
