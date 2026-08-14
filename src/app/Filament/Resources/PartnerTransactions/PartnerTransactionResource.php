<?php

namespace App\Filament\Resources\PartnerTransactions;

use App\Filament\Resources\PartnerTransactions\Pages\CreatePartnerTransaction;
use App\Filament\Resources\PartnerTransactions\Pages\EditPartnerTransaction;
use App\Filament\Resources\PartnerTransactions\Pages\ListPartnerTransactions;
use App\Filament\Resources\PartnerTransactions\Pages\ViewPartnerTransaction;
use App\Filament\Resources\PartnerTransactions\Schemas\PartnerTransactionForm;
use App\Filament\Resources\PartnerTransactions\Tables\PartnerTransactionsTable;
use App\Models\PartnerTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PartnerTransactionResource extends Resource
{
    protected static ?string $model = PartnerTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'امور مالی';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'تراکنش مالی';

    protected static ?string $pluralModelLabel = 'تراکنش‌های مالی';

    protected static ?string $navigationLabel = 'تراکنش‌های مالی';

    public static function form(Schema $schema): Schema
    {
        return PartnerTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartnerTransactionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartnerTransactions::route('/'),
            'create' => CreatePartnerTransaction::route('/create'),
            'view' => ViewPartnerTransaction::route('/{record}'),
            'edit' => EditPartnerTransaction::route('/{record}/edit'),
        ];
    }
}
