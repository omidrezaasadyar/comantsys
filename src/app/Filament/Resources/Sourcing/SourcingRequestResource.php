<?php

namespace App\Filament\Resources\Sourcing;

use App\Filament\Resources\Sourcing\Pages\CreateSourcingRequest;
use App\Filament\Resources\Sourcing\Pages\EditSourcingRequest;
use App\Filament\Resources\Sourcing\Pages\ListSourcingRequests;
use App\Filament\Resources\Sourcing\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\Sourcing\RelationManagers\RunsRelationManager;
use App\Filament\Resources\Sourcing\Schemas\SourcingRequestForm;
use App\Filament\Resources\Sourcing\Tables\SourcingRequestsTable;
use App\Models\SourcingRequest;
use App\Models\SourcingRun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SourcingRequestResource extends Resource
{
    protected static ?string $model = SourcingRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'part_name';

    protected static string|UnitEnum|null $navigationGroup = 'فروش و تأمین';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('sourcing.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('sourcing.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('sourcing.nav');
    }

    /**
     * Add the runs count and the latest run's status (correlated subquery,
     * no N+1) so the list table can render them without extra queries.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select('sourcing_requests.*')
            ->withCount('runs')
            ->addSelect([
                'latest_run_status' => SourcingRun::query()
                    ->select('status')
                    ->whereColumn('sourcing_request_id', 'sourcing_requests.id')
                    ->orderByDesc('id')
                    ->limit(1),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return SourcingRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SourcingRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RunsRelationManager::class,
            AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSourcingRequests::route('/'),
            'create' => CreateSourcingRequest::route('/create'),
            'edit' => EditSourcingRequest::route('/{record}/edit'),
        ];
    }
}
