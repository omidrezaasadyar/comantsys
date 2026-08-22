<?php

namespace App\Filament\Resources\Tasks;

use App\Filament\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\Schemas\TaskForm;
use App\Filament\Resources\Tasks\Tables\TasksTable;
use App\Models\Task;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'امور روزانه';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'پیگیری';
    }

    public static function getPluralModelLabel(): string
    {
        return 'پیگیری‌ها';
    }

    public static function getNavigationLabel(): string
    {
        return 'ثبت و پیگیری';
    }

    public static function form(Schema $schema): Schema
    {
        return TaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }

    /**
     * OWNER SCOPE — second security layer, complementing TaskPolicy.
     *
     * Every query this resource builds (list, count, record lookup) is narrowed
     * to rows the signed-in user is the ASSIGNEE or the CREATOR of, unless they
     * are super_admin, so one user can never read another's tasks. An explicit
     * null-user guard denies an unauthenticated query by construction (1 = 0)
     * rather than relying on the data, since created_by is nullable.
     * parent::getEloquentQuery() is called first so Filament's own base query is
     * preserved.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        // Fail closed by construction: no authenticated user => no rows,
        // regardless of data. created_by is nullable, so we must NOT rely on
        // "created_by is null" matching nothing.
        if ($user === null) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        // super_admin sees all; everyone else is scoped to tasks they are the
        // assignee OR the creator of. Nested closure keeps the OR grouped so it
        // cannot leak past other WHERE constraints (search, filters).
        if (! $user->hasRole('super_admin')) {
            $query->where(function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        return $query;
    }
}
