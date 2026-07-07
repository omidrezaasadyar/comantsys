<?php

namespace App\Filament\Resources\Sourcing\RelationManagers;

use App\Models\SourcingRun;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only view of the agent runs for a sourcing request. Rows are produced
 * solely by App\Jobs\RunSourcingAgent — no create/edit/delete here. The table
 * polls so status badges (and the live elapsed indicator) advance while a run
 * is in progress, without a manual refresh.
 */
class RunsRelationManager extends RelationManager
{
    protected static string $relationship = 'runs';

    /** status value ⇒ Filament badge color. */
    private const STATUS_COLORS = [
        'pending'   => 'gray',
        'running'   => 'info',
        'completed' => 'success',
        'failed'    => 'danger',
    ];

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('sourcing.section.runs');
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('10s')
            ->columns([
                TextColumn::make('status')
                    ->label(__('sourcing.run.field.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('sourcing.run_status.' . $state))
                    ->color(fn (string $state): string => self::STATUS_COLORS[$state] ?? 'gray'),

                TextColumn::make('query')
                    ->label(__('sourcing.run.field.query'))
                    ->limit(40)
                    ->tooltip(fn (SourcingRun $record): ?string => $record->query)
                    ->placeholder('—'),

                TextColumn::make('suppliers_count')
                    ->label(__('sourcing.run.field.suppliers_count'))
                    ->getStateUsing(function (SourcingRun $record): ?int {
                        $suppliers = data_get($record->results, 'suppliers');

                        return is_array($suppliers) ? count($suppliers) : null;
                    })
                    ->placeholder('—'),

                TextColumn::make('tokens')
                    ->label(__('sourcing.run.field.tokens'))
                    ->getStateUsing(fn (SourcingRun $record): string => (int) $record->input_tokens . ' / ' . (int) $record->output_tokens),

                TextColumn::make('started_at')
                    ->label(__('sourcing.run.field.started_at'))
                    ->jalaliDateTime('Y/m/d H:i')
                    ->placeholder('—'),

                // Live elapsed time for in-progress runs; recomputed each poll cycle.
                TextColumn::make('elapsed')
                    ->label(__('sourcing.run.field.elapsed'))
                    ->placeholder('—')
                    ->getStateUsing(function (SourcingRun $record): ?string {
                        if ($record->status !== 'running' || $record->started_at === null) {
                            return null;
                        }

                        $seconds = max(0, $record->started_at->diffInSeconds(now()));

                        if ($seconds < 60) {
                            return __('sourcing.elapsed.seconds', ['count' => $seconds]);
                        }

                        if ($seconds < 3600) {
                            return __('sourcing.elapsed.minutes', ['count' => intdiv($seconds, 60)]);
                        }

                        return __('sourcing.elapsed.hours', ['count' => intdiv($seconds, 3600)]);
                    }),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('sourcing.action.view'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(__('sourcing.detail_title'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('sourcing.close'))
                    ->modalContent(fn (SourcingRun $record): View => view(
                        'filament.sourcing.run-result',
                        ['record' => $record],
                    )),
            ]);
    }
}
