<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\Supplier;
use Filament\Widgets\Widget;

class StatsOverviewWidget extends Widget
{
    protected string $view = 'filament.widgets.stats-overview-widget';

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        return [
            'cards' => [
                [
                    'label' => __('dashboard.stat_invoices'),
                    'count' => Invoice::count(),
                    'url'   => InvoiceResource::getUrl('index'),
                    'icon'  => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'color' => '#3b82f6',
                ],
                [
                    'label' => __('dashboard.stat_sales'),
                    'count' => Sale::count(),
                    'url'   => SaleResource::getUrl('index'),
                    'icon'  => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                    'color' => '#22c55e',
                ],
                [
                    'label' => __('dashboard.stat_letters'),
                    'count' => 0,
                    'url'   => '#',
                    'icon'  => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                    'color' => '#f59e0b',
                ],
                [
                    'label' => __('dashboard.stat_customers'),
                    'count' => Customer::count(),
                    'url'   => CustomerResource::getUrl('index'),
                    'icon'  => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z',
                    'color' => '#06b6d4',
                ],
                [
                    'label' => __('dashboard.stat_suppliers'),
                    'count' => Supplier::count(),
                    'url'   => SupplierResource::getUrl('index'),
                    'icon'  => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1',
                    'color' => '#94a3b8',
                ],
            ],
        ];
    }
}
