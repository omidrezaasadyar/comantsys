<?php
namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SupplierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'md' => 2,
                'xl' => 4,
            ])
            ->inlineLabel()
            ->components([
                Section::make("اطلاعات اصلی")
                    ->beforeLabel(Icon::make(Heroicon::OutlinedInformationCircle))
                    ->columns(1)
                    ->schema([
                        TextEntry::make("name")
                            ->label("نام"),
                        TextEntry::make("is_active")
                            ->label("وضعیت")
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? "فعال" : "غیرفعال")
                            ->color(fn (bool $state): string => $state ? "success" : "gray"),
                            TextEntry::make("parts.part_name")
                            ->label("قطعات")
                            ->badge()
                            ->placeholder("-"),
                    ]),
                Section::make("موقعیت مکانی")
                    ->beforeLabel(Icon::make(Heroicon::OutlinedMapPin))
                    ->columns(1)
                    ->schema([
                        TextEntry::make("country")
                            ->label("کشور")
                            ->placeholder("-"),
                        TextEntry::make("city")
                            ->label("شهر")
                            ->placeholder("-"),
                        TextEntry::make("address")
                            ->label("نشانی")
                            ->inlineLabel(false)
                            ->extraAttributes(['class' => 'entry-fullwidth'])
                            ->placeholder("-"),
                    ]),
                Section::make("راه‌های ارتباطی")
                    ->beforeLabel(Icon::make(Heroicon::OutlinedPhone))
                    ->columns(1)
                    ->schema([
                        TextEntry::make("phone")
                            ->label("تلفن")
                            ->placeholder("-"),
                        TextEntry::make("email")
                            ->label("ایمیل")
                            ->placeholder("-"),
                        TextEntry::make("website")
                            ->label("وب‌سایت")
                            ->placeholder("-"),
                    ]),
                Section::make("اطلاعات تکمیلی")
                    ->beforeLabel(Icon::make(Heroicon::OutlinedDocumentText))
                    ->columns(1)
                    ->schema([
                        TextEntry::make("tags")
                            ->label("برچسب‌ها")
                            ->placeholder("-"),
                        TextEntry::make("notes")
                            ->label("یادداشت‌ها")
                            ->inlineLabel(false)
                            ->extraAttributes(['class' => 'entry-fullwidth'])
                            ->placeholder("-"),

                    ]),
            ]);
    }
}
