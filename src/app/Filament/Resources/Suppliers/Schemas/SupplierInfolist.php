<?php
namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make("اطلاعات اصلی")
                    ->description("نام و وضعیت تأمین‌کننده")
                    ->columns(2)
                    ->schema([
                        TextEntry::make("name")
                            ->label("نام تأمین‌کننده")
                            ->columnSpanFull(),
                        IconEntry::make("is_active")
                            ->label("فعال")
                            ->boolean(),
                    ]),
                Section::make("موقعیت مکانی")
                    ->columns(2)
                    ->schema([
                        TextEntry::make("country")
                            ->label("کشور")
                            ->placeholder("-"),
                        TextEntry::make("city")
                            ->label("شهر")
                            ->placeholder("-"),
                        TextEntry::make("address")
                            ->label("نشانی")
                            ->placeholder("-")
                            ->columnSpanFull(),
                    ]),
                Section::make("راه‌های ارتباطی")
                    ->columns(2)
                    ->schema([
                        TextEntry::make("phone")
                            ->label("تلفن")
                            ->placeholder("-"),
                        TextEntry::make("email")
                            ->label("ایمیل")
                            ->placeholder("-"),
                        TextEntry::make("website")
                            ->label("وب‌سایت")
                            ->placeholder("-")
                            ->columnSpanFull(),
                    ]),
                Section::make("اطلاعات تکمیلی")
                    ->columns(2)
                    ->schema([
                        TextEntry::make("tags")
                            ->label("برچسب‌ها")
                            ->placeholder("-"),
                        TextEntry::make("notes")
                            ->label("یادداشت‌ها")
                            ->placeholder("-")
                            ->columnSpanFull(),
                        TextEntry::make("created_at")
                            ->label("تاریخ ایجاد")
                            ->dateTime()
                            ->placeholder("-"),
                        TextEntry::make("updated_at")
                            ->label("آخرین بروزرسانی")
                            ->dateTime()
                            ->placeholder("-"),
                    ]),
            ]);
    }
}
