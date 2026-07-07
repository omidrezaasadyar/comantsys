<?php

namespace App\Filament\Resources\Sourcing\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SourcingRequestsTable
{
    /** latest-run status value ⇒ Filament badge color. */
    private const RUN_STATUS_COLORS = [
        'pending'   => 'gray',
        'running'   => 'info',
        'completed' => 'success',
        'failed'    => 'danger',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('10s')
            ->columns([
                TextColumn::make('part_name')
                    ->label(__('sourcing.field.part_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('part_number')
                    ->label(__('sourcing.field.part_number'))
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                // Populated by the resource query's correlated subquery.
                TextColumn::make('latest_run_status')
                    ->label(__('sourcing.field.latest_run_status'))
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state): ?string => filled($state) ? __('sourcing.run_status.' . $state) : null)
                    ->color(fn (?string $state): string => self::RUN_STATUS_COLORS[$state] ?? 'gray'),

                TextColumn::make('runs_count')
                    ->label(__('sourcing.field.runs_count'))
                    ->badge()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label(__('sourcing.field.created_at'))
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('sourcing.field.request_status'))
                    ->options([
                        'active'   => __('sourcing.request_status.active'),
                        'archived' => __('sourcing.request_status.archived'),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
