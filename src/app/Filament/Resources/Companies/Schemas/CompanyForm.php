<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('هویت شرکت')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('نام (فارسی)')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('name_en')
                            ->label('نام (انگلیسی)')
                            ->maxLength(255),

                        TextInput::make('national_id')
                            ->label('شناسه ملی')
                            ->maxLength(255),

                        TextInput::make('economic_code')
                            ->label('کد اقتصادی')
                            ->maxLength(255),

                        TextInput::make('registration_no')
                            ->label('شمارهٔ ثبت')
                            ->maxLength(255),
                        TextInput::make('verify_url_base')
                            ->label('آدرس پایهٔ تأیید سند (برای QR)')
                            ->placeholder('https://magan.ir/verify/')
                            ->helperText('شمارهٔ فاکتور به انتهای این آدرس اضافه می‌شود')
                            ->maxLength(255),
                    ]),

                Section::make('اطلاعات تماس')
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')
                            ->label('تلفن ثابت')
                            ->tel()
                            ->maxLength(255),

                        TextInput::make('mobile')
                            ->label('موبایل')
                            ->tel()
                            ->maxLength(255),

                        TextInput::make('messenger_phone')
                            ->label('شمارهٔ پیام‌رسان‌ها')
                            ->tel()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('ایمیل')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('website')
                            ->label('وب‌سایت')
                            ->maxLength(255),

                        TextInput::make('postal_code')
                            ->label('کد پستی')
                            ->maxLength(255),

                        Textarea::make('address')
                            ->label('آدرس (فارسی)')
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('address_en')
                            ->label('آدرس (انگلیسی)')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('لوگو و مهر')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('لوگو')
                            ->image()
                            ->directory('companies/logos')
                            ->maxSize(2048),

                        FileUpload::make('stamp_path')
                            ->label('مهر / امضا')
                            ->image()
                            ->directory('companies/stamps')
                            ->maxSize(2048),
                    ]),

                Section::make('تنظیمات فاکتور و شماره‌گذاری')
                    ->columns(2)
                    ->schema([
                        Select::make('locale')
                            ->label('زبان / تقویم')
                            ->options([
                                'fa' => 'فارسی (شمسی، راست‌چین)',
                                'en' => 'انگلیسی (میلادی، چپ‌چین)',
                            ])
                            ->default('fa')
                            ->required(),

                        Select::make('default_currency')
                            ->label('ارز پیش‌فرض')
                            ->options([
                                'IRR' => 'ریال',
                                'EUR' => 'یورو',
                                'USD' => 'دلار',
                                'GBP' => 'پوند',
                            ])
                            ->default('IRR')
                            ->required(),

                        TextInput::make('prefix')
                            ->label('پیشوند شماره (مثل MAG)')
                            ->required()
                            ->maxLength(10),

                        Select::make('counter_reset')
                            ->label('ریست شمارنده')
                            ->options([
                                'monthly' => 'ماهانه',
                                'yearly'  => 'سالانه',
                                'never'   => 'هرگز',
                            ])
                            ->default('monthly')
                            ->required(),

                        TextInput::make('seq_start')
                            ->label('شمارهٔ شروع هر دوره')
                            ->numeric()
                            ->default(1)
                            ->required(),

                        TextInput::make('seq_padding')
                            ->label('تعداد رقم شماره (مثلاً ۴ → 0029)')
                            ->numeric()
                            ->default(4)
                            ->required(),

                        Textarea::make('footer_note')
                            ->label('پاورقی PDF')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
