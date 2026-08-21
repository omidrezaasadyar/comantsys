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
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Cache;
use Morilog\Jalali\Jalalian;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    /**
     * Authenticated user box (name + avatar initial).
     * Greeting/labels live in the view via __(); this only carries data.
     */
    public function getUserBox(): array
    {
        $user = Filament::auth()->user();
        $name = trim((string) ($user?->name ?? ''));
        $initial = mb_strtoupper(mb_substr($name, 0, 1));

        return [
            'name'    => $name,
            'initial' => $initial !== '' ? $initial : '?',
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
     * Today's tasks — DECORATIVE (no task model exists). Kept per your decision.
     * Colors are inline here because they are purely presentational.
     *
     * @return array<int, array{time:string, title:string, sub:string, color:string}>
     */
    public function getTasks(): array
    {
        return [
            ['time' => '۰۹:۳۰', 'title' => 'تماس با تأمین‌کننده پارس‌تجهیز',   'sub' => 'پیگیری سفارش قطعات',    'color' => '#26a269'],
            ['time' => '۱۱:۰۰', 'title' => 'جلسه بررسی فروش هفتگی',          'sub' => 'اتاق کنفرانس · تیم فروش', 'color' => '#f56a1c'],
            ['time' => '۱۴:۰۰', 'title' => 'ارسال فاکتور به صنایع فولاد شرق',  'sub' => 'فاکتور INV-۱۰۴۱',      'color' => '#4a7fd6'],
            ['time' => '۱۶:۳۰', 'title' => 'پیگیری فاکتورهای معوق',          'sub' => '۲ فاکتور سررسید گذشته',  'color' => '#e05260'],
            ['time' => '۱۷:۰۰', 'title' => 'آماده‌سازی گزارش ماهانه فروش',     'sub' => 'مرداد ۱۴۰۵',           'color' => '#26a269'],
            ['time' => '۱۸:۰۰', 'title' => 'بررسی موجودی انبار قطعات',       'sub' => 'انبار مرکزی',          'color' => '#2bb3c0'],
            ['time' => '۱۸:۳۰', 'title' => 'پاسخ به استعلام مشتری عمان',      'sub' => 'EIS · مسقط',           'color' => '#f0932b'],
        ];
    }
}
