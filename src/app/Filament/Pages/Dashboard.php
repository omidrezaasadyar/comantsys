<?php

namespace App\Filament\Pages;

use App\Contracts\RateProviderInterface;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\PortalRequests\PortalRequestResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PortalRequest;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Task;
use App\Support\NumberToWords;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

/**
 * NOTE — action hosting is INHERITED, not declared here.
 * Filament\Pages\BasePage (the ancestor of Filament\Pages\Dashboard) already
 * declares `implements HasActions` and `use InteractsWithActions`
 * (vendor/filament/filament/src/Pages/BasePage.php:20-22), so re-declaring the
 * trait on this subclass would only shadow the parent's copy for no gain.
 */
class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    /**
     * Authenticated user box: name, avatar initial, and humanized role(s).
     * Roles come from spatie HasRoles; multiple roles are comma-joined and
     * each name is humanized (super_admin -> "Super Admin"). Empty when the
     * user has no role, so the view can hide the line.
     *
     * @return array{name:string, initial:string, role:string}
     */
    public function getUserBox(): array
    {
        $user = Filament::auth()->user();
        $name = trim((string) ($user?->name ?? ''));
        $initial = mb_strtoupper(mb_substr($name, 0, 1));

        $role = '';
        if ($user !== null && method_exists($user, 'getRoleNames')) {
            $role = $user->getRoleNames()
                ->map(fn (string $r): string => Str::headline($r))
                ->implode('، '); // Persian comma; fine in both locales
        }

        return [
            'name'    => $name,
            'initial' => $initial !== '' ? $initial : '?',
            'role'    => $role,
        ];
    }

    /**
     * Live dates for the dashboard.
     * Display is always Jalali (project convention); only the DIGIT SHAPE is
     * locale-aware — Persian digits under fa, Latin digits under en — so the
     * fa panel matches the rest of the Persian UI while the en panel stays
     * Latin. Gregorian is always Latin Y/m/d. Weekday/month names come from
     * Morilog\Jalali and are Persian in both locales (there is no en Jalali
     * month set; that is acceptable for this display line).
     *
     * @return array{jalali:string, jalali_long:string, gregorian:string}
     */
    public function getDates(): array
    {
        $jalali     = Jalalian::forge(now())->format('Y/m/d');
        $jalaliLong = Jalalian::forge(now())->format('l j F Y');

        // Under Persian locale, render the numerals in Persian digits.
        if (app()->getLocale() === 'fa') {
            $latin   = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $jalali     = str_replace($latin, $persian, $jalali);
            $jalaliLong = str_replace($latin, $persian, $jalaliLong);
        }

        return [
            'jalali'      => $jalali,
            'jalali_long' => $jalaliLong,
            'gregorian'   => now()->format('Y/m/d'),
        ];
    }

    /**
     * KPI counts — REAL. Colors/labels/icons/sparklines live in the view.
     * 'letters' stays 0 / '#' to match the current widget (no letters module yet).
     *
     * @return array<string, array{count:int, url:string}>
     */
    public function getStats(): array
    {
        return [
            'invoices'  => ['count' => Invoice::count(),  'url' => InvoiceResource::getUrl('index')],
            'sales'     => ['count' => Sale::count(),     'url' => SaleResource::getUrl('index')],
            'customers' => ['count' => Customer::count(), 'url' => CustomerResource::getUrl('index')],
            'suppliers' => ['count' => Supplier::count(), 'url' => SupplierResource::getUrl('index')],
            'letters'   => ['count' => 0,                 'url' => '#'],
        ];
    }

    /**
     * Quick-action targets — REAL routes (all verified to exist).
     * new_invoice / proforma both open the invoice create page; pre-selecting
     * `type` is deferred until the enum values + create-page handling are
     * confirmed (kept out on purpose to avoid guessing).
     *
     * @return array<string, string>
     */
    public function getQuickActionUrls(): array
    {
        return [
            'new_invoice' => InvoiceResource::getUrl('create'),
            'proforma'    => InvoiceResource::getUrl('create'),
            'customer'    => CustomerResource::getUrl('create'),
            'letter'      => InquiryResource::getUrl('create'),
            'request'     => PortalRequestResource::getUrl('index'),
            'reports'     => '#',
        ];
    }

    /**
     * Live tgju USD/EUR rates for the dashboard chips.
     *
     * Served through a two-layer cache so a page load never blocks on tgju
     * beyond the first miss, and never shows an empty chip once we have data:
     *   L1  'dashboard.rates'      — 30-min TTL, the value actually served
     *   L2  'dashboard.rates.last' — forever, the last KNOWN-GOOD snapshot
     * On an L1 miss we ask the provider; on success we refresh L2 and return
     * fresh; on provider failure we fall back to L2. If neither exists yet
     * (very first run while tgju is unreachable), returns null and the view
     * shows a graceful placeholder.
     *
     * Shape per currency mirrors the provider: value (Toman), delta, dir.
     *
     * @return array{
     *   usd: array{value:string, delta:string, dir:string},
     *   eur: array{value:string, delta:string, dir:string}
     * }|null
     */
    public function getRates(): ?array
    {
        return Cache::remember('dashboard.rates', now()->addMinutes(30), function (): ?array {
            $fresh = app(RateProviderInterface::class)->rates();

            if ($fresh !== null) {
                // Persist the last good snapshot for offline fallback.
                Cache::forever('dashboard.rates.last', $fresh);

                return $fresh;
            }

            // Provider failed this cycle — reuse the last good snapshot if any.
            return Cache::get('dashboard.rates.last');
        });
    }

    /**
     * Received requests — REAL data now.
     * Newest first, capped at 6 for the panel. Shape is unchanged from the
     * previous decorative version, so the view needs no structural change:
     *   title  ← subject (falls back to request_number so a row is never blank)
     *   who    ← requester_name
     *   ago    ← created_at->diffForHumans() (relative, locale-driven)
     *   status ← request_status (drives dot color + label via the model maps)
     *
     * @return array<int, array{title:string, who:string, ago:string, status:string}>
     */
    public function getRequests(): array
    {
        return PortalRequest::query()
            ->latest()
            ->limit(6)
            ->get(['id', 'request_number', 'subject', 'requester_name', 'request_status', 'created_at'])
            ->map(fn (PortalRequest $r): array => [
                'title'  => filled($r->subject) ? $r->subject : $r->request_number,
                'who'    => (string) ($r->requester_name ?? '—'),
                'ago'    => optional($r->created_at)->diffForHumans() ?? '—',
                'status' => (string) ($r->request_status ?? 'received'),
            ])
            ->all();
    }

    /**
     * Total number of received requests (for the panel's count pill).
     * The list above is capped at 6; this is the true total, so the pill can
     * read "N items" even when more than 6 exist.
     */
    public function getRequestsTotal(): int
    {
        return PortalRequest::count();
    }

    /**
     * Today's tasks — REAL, owner-scoped data from the Task model.
     *
     * Open tasks only (is_done = false), soonest due first, capped at 8.
     * Owner scope is intentionally NARROWER than TaskResource::getEloquentQuery():
     * that lists assignee OR creator, but this dashboard panel shows only tasks
     * assigned to the current user (user_id = me) — "my tasks to do today", not
     * work I handed off. super_admin sees all.
     *
     * Returns real owner-scoped tasks; each row carries its id so the dashboard
     * can open the completeTask modal via mountAction:
     *   id    ← primary key, passed as mountAction('completeTask', { task: id })
     *   time  ← due_date as a short Jalali date ("۲۹ مرداد"), Persian digits
     *   title ← title
     *   sub   ← completion_note, falling back to the assignee name for
     *           super_admin (who sees other people's rows) and '' otherwise
     *   color ← due_date vs today: overdue red, due today orange, future blue.
     *           Purely presentational, hence inline hex.
     *
     * @return array<int, array{id:int, time:string, title:string, sub:string, color:string}>
     */
    public function getTasks(): array
    {
        $isSuperAdmin = auth()->user()?->hasRole('super_admin') ?? false;

        $query = Task::query()
            ->with('user:id,name')
            ->where('is_done', false);

        if (! $isSuperAdmin) {
            $query->where('user_id', auth()->id());
        }

        $today = now()->startOfDay();

        return $query
            ->orderBy('due_date')
            ->limit(8)
            ->get()
            ->map(function (Task $task) use ($isSuperAdmin, $today): array {
                $due = $task->due_date->copy()->startOfDay();

                $color = match (true) {
                    $due->lt($today) => '#e05260', // overdue
                    $due->eq($today) => '#f56a1c', // due today
                    default          => '#4a7fd6', // upcoming
                };

                $sub = filled($task->completion_note)
                    ? $task->completion_note
                    : ($isSuperAdmin ? (string) ($task->user?->name ?? '') : '');

                return [
                    'id'    => $task->id,
                    'time'  => NumberToWords::toPersianDigits(
                        Jalalian::fromDateTime($task->due_date)->format('j F')
                    ),
                    'title' => (string) $task->title,
                    'sub'   => $sub,
                    'color' => $color,
                ];
            })
            ->all();
    }

    /**
     * Scoped Task lookup for the dashboard action — SECURITY LAYER 1.
     *
     * Mirrors TaskResource::getEloquentQuery(): super_admin sees every task,
     * everyone else only rows they are the assignee OR the creator of, and an
     * unauthenticated request is denied by construction (1 = 0) rather than by
     * relying on data. The OR sits in a nested closure so it cannot leak past
     * any later constraint.
     *
     * The id arrives from the browser via the action's arguments, so it is
     * validated as numeric first: PostgreSQL raises 22P02 (invalid input syntax
     * for bigint) if a non-numeric string reaches a bigint primary key.
     */
    protected function findScopedTask(mixed $id): ?Task
    {
        if (blank($id) || ! is_numeric($id)) {
            return null;
        }

        $user = auth()->user();
        $query = Task::query();

        if ($user && ! $user->hasRole('super_admin')) {
            $query->where(function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        } elseif (! $user) {
            $query->whereRaw('1 = 0');
        }

        return $query->with('creator:id,name')->find((int) $id);
    }

    /**
     * Short Jalali due date ("۲۹ مرداد"), Persian digits — same format the
     * today-tasks list already uses, so the modal reads consistently with it.
     */
    protected function taskDueLabel(?Task $task): string
    {
        if ($task?->due_date === null) {
            return '—';
        }

        return NumberToWords::toPersianDigits(
            Jalalian::fromDateTime($task->due_date)->format('j F')
        );
    }

    /**
     * Human due-status: overdue / today / N days remaining (Persian digits).
     */
    protected function taskDueStatusLabel(?Task $task): string
    {
        if ($task?->due_date === null) {
            return '—';
        }

        $today = now()->startOfDay();
        $due = $task->due_date->copy()->startOfDay();

        if ($due->lt($today)) {
            return 'سررسید گذشته';
        }

        if ($due->eq($today)) {
            return 'امروز';
        }

        return NumberToWords::toPersianDigits((string) (int) $today->diffInDays($due)).' روز مانده';
    }

    /**
     * Due-date colour for the modal summary — overdue red, due-today orange,
     * upcoming blue. Same thresholds and hexes as getTasks(); kept as its own
     * method because that one builds them inside a map() over a collection.
     */
    private function taskDueColor(?Task $task): string
    {
        if ($task?->due_date === null) {
            return '#4a7fd6';
        }

        $today = now()->startOfDay();
        $due = $task->due_date->copy()->startOfDay();

        return match (true) {
            $due->lt($today) => '#e05260', // overdue
            $due->eq($today) => '#f56a1c', // due today
            default          => '#4a7fd6', // upcoming
        };
    }

    /**
     * Everything the completeTask modal's read-only Blade partial needs.
     * Null-safe throughout: the schema closure runs before the mount guards
     * have necessarily rejected an unknown id, so $task may legitimately be null.
     *
     * @return array{title:string, creator:string, due:string, dueStatus:string, dueColor:string}
     */
    private function taskModalData(?Task $task): array
    {
        return [
            'title'     => $task?->title ?? '—',
            'creator'   => $task?->creator?->name ?? '—',
            'due'       => $this->taskDueLabel($task),
            'dueStatus' => $this->taskDueStatusLabel($task),
            'dueColor'  => $this->taskDueColor($task),
        ];
    }

    /**
     * Modal action: tick "done" and write a note without leaving the dashboard.
     *
     * Mounted from the view as wire:click="mountAction('completeTask', { task: ID })",
     * so the record id arrives in the action's arguments (Action injects a
     * closure parameter named $arguments — vendor Action.php:558).
     *
     * TWO security layers, both re-run on submit rather than trusting a bound
     * record: findScopedTask() narrows the query (layer 1), and TaskPolicy via
     * can('update') authorises the write (layer 2).
     */
    public function completeTaskAction(): Action
    {
        return Action::make('completeTask')
            ->modalHeading('ثبت انجام پیگیری')
            ->modalSubmitActionLabel('ذخیره')
            ->modalWidth(Width::Medium)
            // mountUsing() rather than fillForm(): fillForm() IS a mountUsing()
            // wrapper (vendor CanBeMounted.php:31), so declaring both would make
            // the second silently replace the first. Doing it by hand lets the
            // not-found case cancel the mount before the modal opens.
            ->mountUsing(function (Action $action, ?Schema $schema, array $arguments): void {
                $task = $this->findScopedTask($arguments['task'] ?? null);

                if ($task === null) {
                    Notification::make()->title('یافت نشد')->danger()->send();

                    // Throws Cancel, which mountAction() catches and unmounts —
                    // the modal never opens.
                    $action->cancel();

                    return;
                }

                // SECURITY LAYER 2, mount-time copy: refuse early rather than
                // after the user has filled the form. The save-time check in
                // action() stays as well — defence in depth.
                if (! auth()->user()?->can('update', $task)) {
                    Notification::make()
                        ->title('اجازه ندارید')
                        ->danger()
                        ->send();

                    $action->cancel();

                    return;
                }

                $schema?->fill([
                    'is_done' => (bool) $task->is_done,
                    'completion_note' => $task->completion_note,
                ]);
            })
            // schema(), not the deprecated form() alias (vendor HasSchema.php:26 vs 128).
            ->schema(function (array $arguments): array {
                $task = $this->findScopedTask($arguments['task'] ?? null);

                return [
                    // Read-only summary is a bespoke Blade view rather than four
                    // TextEntry rows, so the modal can style the due-date state.
                    // Same pattern as ViewPortalRequest.php:86.
                    View::make('filament.pages.task-modal')
                        ->viewData(fn (): array => $this->taskModalData($task)),

                    Toggle::make('is_done')
                        ->label('انجام شد'),

                    TextInput::make('completion_note')
                        ->label('توضیح')
                        ->maxLength(255),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                // Re-resolve through the scoped query — never trust a record
                // bound at mount time, the modal may have sat open for a while.
                $task = $this->findScopedTask($arguments['task'] ?? null);

                if ($task === null) {
                    Notification::make()->title('یافت نشد')->danger()->send();

                    return;
                }

                if (! (auth()->user()?->can('update', $task) ?? false)) {
                    Notification::make()->title('اجازه ندارید')->danger()->send();

                    return;
                }

                $task->is_done = (bool) ($data['is_done'] ?? false);
                $task->completion_note = $data['completion_note'] ?? null;

                // done_at is managed by the model's saving() hook.
                $task->save();

                Notification::make()->title('ذخیره شد')->success()->send();
            });
    }
}
