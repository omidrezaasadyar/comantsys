<?php

namespace App\Filament\Portal\Resources\PortalRequests;

use App\Filament\Portal\Resources\PortalRequests\Pages\CreatePortalRequest;
use App\Filament\Portal\Resources\PortalRequests\Pages\EditPortalRequest;
use App\Filament\Portal\Resources\PortalRequests\Pages\ListPortalRequests;
use App\Filament\Portal\Resources\PortalRequests\Pages\ViewPortalRequest;
use App\Filament\Portal\Resources\PortalRequests\Schemas\PortalRequestForm;
use App\Filament\Portal\Resources\PortalRequests\Tables\PortalRequestsTable;
use App\Models\PortalRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PortalRequestResource extends Resource
{
    protected static ?string $model = PortalRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $recordTitleAttribute = 'request_number';

    public static function getNavigationLabel(): string
    {
        return __('portal_requests.nav');
    }

    public static function getPluralModelLabel(): string
    {
        return __('portal_requests.plural');
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
     * List + create + read-only view + a GATED edit.
     *
     * Every record route resolves through getEloquentQuery() below, so the
     * owner scope covers all of them without extra checks on the pages. Edit
     * carries a second gate of its own — it opens only while request_status is
     * 'needs_revision' (see EditPortalRequest::canAccess()).
     */
    public static function getPages(): array
    {
        return [
            'index' => ListPortalRequests::route('/'),
            'create' => CreatePortalRequest::route('/create'),
            'view' => ViewPortalRequest::route('/{record}'),
            'edit' => EditPortalRequest::route('/{record}/edit'),
        ];
    }

    /**
     * OWNER SCOPE — the security core of the portal.
     *
     * Every query this resource builds (list, count, and any future record
     * lookup) is narrowed to the signed-in user's own rows, so one portal
     * customer can never read another's requests. parent::getEloquentQuery()
     * is called first so Filament's own base query is preserved.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }
}
