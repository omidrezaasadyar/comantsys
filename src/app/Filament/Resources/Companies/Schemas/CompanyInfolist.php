<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('هویت شرکت')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('نام (فارسی)'),
                        TextEntry::make('name_en')->label('نام (انگلیسی)'),
                        TextEntry::make('national_id')->label('شناسه ملی'),
                        TextEntry::make('economic_code')->label('کد اقتصادی'),
                        TextEntry::make('registration_no')->label('شمارهٔ ثبت'),
                        TextEntry::make('verify_url_base')->label('آدرس پایهٔ تأیید سند (برای QR)'),
                    ]),

                Section::make('اطلاعات تماس')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('phone')->label('تلفن ثابت'),
                        TextEntry::make('mobile')->label('موبایل'),
                        TextEntry::make('messenger_phone')->label('شمارهٔ پیام‌رسان‌ها'),
                        TextEntry::make('email')->label('ایمیل'),
                        TextEntry::make('website')->label('وب‌سایت'),
                        TextEntry::make('postal_code')->label('کد پستی'),
                        TextEntry::make('address')->label('آدرس (فارسی)')->columnSpanFull(),
                        TextEntry::make('address_en')->label('آدرس (انگلیسی)')->columnSpanFull(),
                    ]),

                Section::make('لوگو و مهر')
                    ->columns(2)
                    ->schema([
                        // Uploaded by CompanyForm's FileUpload onto Filament's default
                        // disk, which here is `local` (storage/app/private). Private
                        // files resolve through the signed `storage.local` route.
                        ImageEntry::make('logo_path')
                            ->label('لوگو')
                            ->disk('local')
                            ->visibility('private'),

                        ImageEntry::make('stamp_path')
                            ->label('مهر / امضا')
                            ->disk('local')
                            ->visibility('private'),
                    ]),

                Section::make('تنظیمات فاکتور و شماره‌گذاری')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('locale')
                            ->label('زبان / تقویم')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'fa' => 'فارسی (شمسی، راست‌چین)',
                                'en' => 'انگلیسی (میلادی، چپ‌چین)',
                                default => '—',
                            }),

                        TextEntry::make('default_currency')
                            ->label('ارز پیش‌فرض')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'IRR' => 'ریال',
                                'EUR' => 'یورو',
                                'USD' => 'دلار',
                                'GBP' => 'پوند',
                                default => '—',
                            }),

                        TextEntry::make('prefix')->label('پیشوند شماره'),

                        TextEntry::make('counter_reset')
                            ->label('ریست شمارنده')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'monthly' => 'ماهانه',
                                'yearly'  => 'سالانه',
                                'never'   => 'هرگز',
                                default => '—',
                            }),

                        TextEntry::make('seq_start')->label('شمارهٔ شروع هر دوره'),
                        TextEntry::make('seq_padding')->label('تعداد رقم شماره'),
                        TextEntry::make('footer_note')->label('پاورقی PDF')->columnSpanFull(),
                    ]),
            ]);
    }
}
