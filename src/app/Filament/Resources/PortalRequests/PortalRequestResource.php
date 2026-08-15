<?php

namespace App\Filament\Resources\PortalRequests;

use App\Filament\Resources\PortalRequests\Pages\EditPortalRequest;
use App\Filament\Resources\PortalRequests\Pages\ListPortalRequests;
use App\Filament\Resources\PortalRequests\Schemas\PortalRequestForm;
use App\Filament\Resources\PortalRequests\Tables\PortalRequestsTable;
use App\Models\PortalRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * ADMIN-side portal requests — the internal review desk.
 *
 * Deliberately separate from App\Filament\Portal\Resources\PortalRequests\
 * PortalRequestResource, which is the customer-facing one. Same model, opposite
 * jobs: the portal resource is owner-scoped and create-only, this one is
 * unscoped and edit-only. Keeping them apart is what stops a change made for
 * operators from widening what a customer can see.
 *
 * Discovery keeps them apart too: AdminPanelProvider scans
 * app/Filament/Resources, PortalPanelProvider scans app/Filament/Portal/
 * Resources, and neither path contains the other.
 */
class PortalRequestResource extends Resource
{
    protected static ?string $model = PortalRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $recordTitleAttribute = 'request_number';

    protected static string|UnitEnum|null $navigationGroup = 'فروش و تأمین';

    /**
     * Sits after Invoices (5) and before Suppliers (10) in the group, which is
     * roughly where the intake desk belongs in the sales/sourcing flow.
     */
    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return __('portal_requests.admin.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('portal_requests.admin.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('portal_requests.admin.nav');
    }

    public static function form(Schema $schema): Schema
    {
        return PortalRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PortalRequestsTable::configure($table);
    }

    /**
     * NO getEloquentQuery() override on purpose.
     *
     * The portal resource narrows every query to `user_id = auth()->id()`;
     * operators must see EVERY request, from every customer, so this resource
     * leaves Filament's base query untouched. Access is gated by Shield
     * policies, not by a row scope. Do not copy the owner scope here.
     */
    public static function getPages(): array
    {
        return [
            // No 'create': requests are created by customers in the portal.
            // No 'view' page yet — the edit screen is the review screen.
            'index' => ListPortalRequests::route('/'),
            'edit' => EditPortalRequest::route('/{record}/edit'),
        ];
    }
}
