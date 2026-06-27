<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات اصلی')
                    ->description('نام و وضعیت تأمین‌کننده')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('نام تأمین‌کننده')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true)
                            ->inline(false),
                    ]),

                Section::make('موقعیت مکانی')
                    ->columns(2)
                    ->schema([
                        TextInput::make('country')
                            ->label('کشور')
                            ->maxLength(255),
                        TextInput::make('city')
                            ->label('شهر')
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('نشانی')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('راه‌های ارتباطی')
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')
                            ->label('تلفن')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('ایمیل')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('website')
                            ->label('وب‌سایت')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('اطلاعات تکمیلی')
                    ->collapsed()
                    ->schema([
                        TextInput::make('tags')
                            ->label('برچسب‌ها')
                            ->helperText('برچسب‌ها را با ویرگول جدا کنید (مثلاً: تجهیزات، برق، ایران).')
                            ->maxLength(255),
                        Textarea::make('notes')
                            ->label('یادداشت‌ها')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
