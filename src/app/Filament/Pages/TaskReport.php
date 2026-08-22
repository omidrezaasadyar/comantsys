<?php

namespace App\Filament\Pages;

use App\Models\Task;
use App\Models\User;
use App\Support\NumberToWords;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Morilog\Jalali\Jalalian;
use UnitEnum;

/**
 * Tasks report — "did the work I handed out actually get done?".
 *
 * Sits next to TaskResource in the «امور روزانه» group (sort 2, after the
 * list at sort 1). The Blade view 'filament.pages.task-report' is built in
 * the NEXT step; this class provides only the navigation, access rule and
 * the three data methods it will consume.
 */
class TaskReport extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static string | UnitEnum | null $navigationGroup = 'امور روزانه';
    protected static ?string $navigationLabel = 'گزارش';
    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.task-report';

    public function getTitle(): string
    {
        return 'گزارش پیگیری‌ها';
    }

    /**
     * Visible to super_admin and to anyone who may hand out work — a user who
     * cannot create a task has nothing delegated to report on.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->hasRole('super_admin') ?? false)
            || ($user?->can('Create:Task') ?? false);
    }

    public function getHeader(): ?View
    {
        return view('filament.records.partials.header-band', [
            'header' => [
                'icon' => Heroicon::OutlinedChartBar,
                'title' => 'گزارش پیگیری‌ها',
                'subtitle' => 'نمای کلی وضعیت پیگیری‌ها بر اساس کاربر و وضعیت انجام.',
                'breadcrumbs' => [
                    ['label' => 'گزارش پیگیری‌ها', 'url' => null],
                ],
            ],
        ]);
    }

    private function isSuperAdmin(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    /**
     * Base query for every figure on this page.
     *
     * Report scope is INTENTIONALLY different from the dashboard and the
     * resource list:
     *   dashboard  = user_id (tasks assigned TO me, to do)
     *   resource   = user_id OR created_by (mine + ones I handed out)
     *   report     = created_by (ones I HANDED OUT — "did my delegated work get done")
     * super_admin sees everything; a null user is denied by construction.
     */
    private function scopedQuery(): Builder
    {
        $user = auth()->user();
        $query = Task::query();

        if (! $this->isSuperAdmin()) {
            if ($user === null) {
                $query->whereRaw('1 = 0'); // fail closed
            } else {
                $query->where('created_by', $user->id);
            }
        }

        return $query;
    }

    /**
     * Headline counters. Each count runs on its own clone of the base query so
     * the where() calls cannot stack onto one another.
     *
     * @return array<string, array{label:string, value:string, color:string}>
     */
    public function getStats(): array
    {
        $base = $this->scopedQuery();
        $today = now()->startOfDay()->toDateString();

        $total = (clone $base)->count();
        $done = (clone $base)->where('is_done', true)->count();
        $pending = (clone $base)->where('is_done', false)->count();
        $overdue = (clone $base)->where('is_done', false)->whereDate('due_date', '<', $today)->count();

        return [
            'total' => [
                'label' => 'کل پیگیری‌ها',
                'value' => NumberToWords::toPersianDigits((string) $total),
                'color' => '#4a7fd6',
            ],
            'done' => [
                'label' => 'انجام‌شده',
                'value' => NumberToWords::toPersianDigits((string) $done),
                'color' => '#26a269',
            ],
            'pending' => [
                'label' => 'در جریان',
                'value' => NumberToWords::toPersianDigits((string) $pending),
                'color' => '#f0932b',
            ],
            'overdue' => [
                'label' => 'معوق',
                'value' => NumberToWords::toPersianDigits((string) $overdue),
                'color' => '#e05260',
            ],
        ];
    }

    /**
     * Per-assignee breakdown — only meaningful for super_admin, who is the only
     * one who sees other people's rows. Returns [] for everyone else so the
     * Blade can skip the whole block.
     *
     * Aggregated in ONE grouped query plus ONE name lookup (no N+1).
     *
     * @return array<int, array{name:string, total:string, done:string, pending:string, overdue:string}>
     */
    public function getUserBreakdown(): array
    {
        if (! $this->isSuperAdmin()) {
            return [];
        }

        $today = now()->startOfDay()->toDateString();

        $rows = $this->scopedQuery()
            ->selectRaw('user_id')
            ->selectRaw('count(*) as total_count')
            ->selectRaw('sum(case when is_done then 1 else 0 end) as done_count')
            ->selectRaw('sum(case when is_done then 0 else 1 end) as pending_count')
            ->selectRaw('sum(case when (not is_done) and due_date < ? then 1 else 0 end) as overdue_count', [$today])
            ->groupBy('user_id')
            ->orderByDesc('total_count')
            ->get();

        $names = User::query()
            ->whereIn('id', $rows->pluck('user_id')->filter()->all())
            ->pluck('name', 'id');

        return $rows
            ->map(fn ($row): array => [
                'name' => (string) ($names[$row->user_id] ?? '—'),
                'total' => NumberToWords::toPersianDigits((string) (int) $row->total_count),
                'done' => NumberToWords::toPersianDigits((string) (int) $row->done_count),
                'pending' => NumberToWords::toPersianDigits((string) (int) $row->pending_count),
                'overdue' => NumberToWords::toPersianDigits((string) (int) $row->overdue_count),
            ])
            ->all();
    }

    /**
     * Detail list — open tasks first, then soonest due. Capped at 100 rows;
     * the cap is deliberate and the Blade should say so rather than imply the
     * list is exhaustive.
     *
     * @return array<int, array{assignee:string, creator:string, title:string, due:string, status:string, statusColor:string}>
     */
    public function getDetailRows(): array
    {
        $today = now()->startOfDay();

        return $this->scopedQuery()
            ->with(['user:id,name', 'creator:id,name'])
            ->orderBy('is_done')
            ->orderBy('due_date')
            ->limit(100)
            ->get()
            ->map(function (Task $task) use ($today): array {
                $isOverdue = ! $task->is_done
                    && $task->due_date !== null
                    && $task->due_date->copy()->startOfDay()->lt($today);

                return [
                    'assignee' => (string) ($task->user?->name ?? '—'),
                    'creator' => (string) ($task->creator?->name ?? '—'),
                    'title' => (string) $task->title,
                    'due' => $task->due_date === null
                        ? '—'
                        : NumberToWords::toPersianDigits(
                            Jalalian::fromDateTime($task->due_date)->format('j F')
                        ),
                    'status' => match (true) {
                        (bool) $task->is_done => 'انجام‌شده',
                        $isOverdue => 'معوق',
                        default => 'در جریان',
                    },
                    'statusColor' => match (true) {
                        (bool) $task->is_done => '#26a269',
                        $isOverdue => '#e05260',
                        default => '#f0932b',
                    },
                ];
            })
            ->all();
    }
}
