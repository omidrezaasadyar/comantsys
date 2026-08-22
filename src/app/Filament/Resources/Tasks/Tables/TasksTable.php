<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Models\Task;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The daily-use surface: users tick "done" and edit a one-line note inline,
 * without opening the form.
 *
 * Two-way model: both the assignee and the creator are shown to everyone, since
 * either side may be looking at the row (TaskResource::getEloquentQuery scopes
 * to assignee OR creator).
 *
 * SECURITY — inline editable columns (ToggleColumn, TextInputColumn) do NOT
 * check model policies before saving; Filament's own CanUpdateState trait says
 * so explicitly. Only disabled() is honoured, and it IS enforced server-side in
 * HasColumns::updateTableColumnState() after the record is bound — so the
 * disabled() closures below are the real guard, routed through TaskPolicy.
 */
class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Two-key default sort (undone first, then earliest due). defaultSort()
            // takes a single column OR a Closure; the Closure form returns a Builder
            // that replaces the query, which is how multi-column ordering is done.
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy('is_done')
                ->orderBy('due_date'))
            ->columns([
                TextColumn::make('user.name')
                    ->label('واگذار به')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('واگذار شده توسط')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('موضوع پیگیری')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('due_date')
                    ->label('تاریخ سررسید')
                    ->jalaliDate()
                    ->sortable(),

                ToggleColumn::make('is_done')
                    ->label('انجام شد')
                    ->disabled(fn (Task $record): bool => ! auth()->user()?->can('update', $record))
                    ->sortable(),

                TextInputColumn::make('completion_note')
                    ->label('توضیح')
                    ->disabled(fn (Task $record): bool => ! auth()->user()?->can('update', $record)),
            ])
            ->filters([
                // Only super_admin sees the whole table, so only they can act on an
                // assignee filter. BaseFilter uses the CanBeHidden concern, so
                // visible() exists on filters exactly as it does on columns.
                SelectFilter::make('user_id')
                    ->label('واگذار به')
                    ->relationship('user', 'name')
                    ->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
            ])
            ->recordActions([
                EditAction::make(),

                // TaskPolicy::delete() allows super_admin or the task's creator;
                // Filament hides the button for anyone who fails it.
                DeleteAction::make(),
            ]);
    }
}
