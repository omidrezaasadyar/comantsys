<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use App\Filament\Resources\Invoices\Schemas\InvoiceInfolist;
use App\Filament\Resources\Invoices\Tables\InvoicesTable;
use App\Models\Invoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'فروش و تأمین';

    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'فاکتورها و پیش‌فاکتورها';

    /**
     * Without these two, HasLabels::getModelLabel() falls all the way through
     * to `get_model_label(static::getModel())`
     * (Resources/Resource/Concerns/HasLabels.php:45), which humanises the class
     * basename — so every label Filament builds by interpolating `:label`
     * leaked English: the create button read «ایجاد invoice», and the bulk
     * delete modal «invoices». `$navigationLabel` only names the sidebar item
     * and does not feed those. Same static-property approach the Company,
     * Customer, Sale and User resources already use.
     */
    protected static ?string $modelLabel = 'فاکتور';

    protected static ?string $pluralModelLabel = 'فاکتورها و پیش‌فاکتورها';
    protected static ?string $recordTitleAttribute = 'invoice_number';

    /**
     * Global search: the parent returns just [$recordTitleAttribute], which is
     * why the old 'name' value crashed on invoices.name. Widened to include the
     * customer, since staff search by who the invoice is for as often as by number.
     *
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['invoice_number', 'customer.name'];
    }

    public static function form(Schema $schema): Schema
    {
        return InvoiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'view' => ViewInvoice::route('/{record}'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
