<?php

namespace App\Filament\Resources\Inquiries;

use App\Filament\Resources\Inquiries\Pages\CreateInquiry;
use App\Filament\Resources\Inquiries\Pages\EditInquiry;
use App\Filament\Resources\Inquiries\Pages\ListInquiries;
use App\Filament\Resources\Inquiries\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\Inquiries\Schemas\InquiryForm;
use App\Filament\Resources\Inquiries\Tables\InquiriesTable;
use App\Models\Inquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static ?string $recordTitleAttribute = 'inquiry_number';

    protected static string|UnitEnum|null $navigationGroup = 'فروش و تأمین';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('inquiries.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inquiries.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('inquiries.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return InquiryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InquiriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInquiries::route('/'),
            'create' => CreateInquiry::route('/create'),
            'edit' => EditInquiry::route('/{record}/edit'),
        ];
    }
}
