<?php

namespace App\Filament\Portal\Resources\PortalRequests;

use App\Filament\Portal\Resources\PortalRequests\Pages\ListPortalRequests;
use App\Filament\Portal\Resources\PortalRequests\Tables\PortalRequestsTable;
use App\Models\PortalRequest;
use BackedEnum;
use Filament\Resources\Resource;
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

    public static function table(Table $table): Table
    {
        return PortalRequestsTable::configure($table);
    }

    /**
     * List only — no create/view/edit pages exist yet.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListPortalRequests::route('/'),
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
