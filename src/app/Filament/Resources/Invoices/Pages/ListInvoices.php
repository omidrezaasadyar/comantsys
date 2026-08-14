<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Same mechanism as the Sale, Customer, Inquiry and Company lists:
     * Filament's page wrapper renders `{{ $header }}` INSTEAD of its default
     * header once getHeader() returns a view — the heading, breadcrumbs and
     * header actions in the `@else` branch are skipped entirely
     * (filament/resources/views/components/page/index.blade.php:45-77), so the
     * default heading is never printed twice. The band renders the actions
     * itself: getCachedHeaderActions() hands over the already-booted
     * CreateAction above, which now reads «ایجاد فاکتور» — see the model label
     * added on InvoiceResource.
     *
     * The icon matches the one ViewInvoice puts on its own band.
     */
    public function getHeader(): ?View
    {
        return view('filament.records.partials.header-band', [
            'header' => [
                'icon' => Heroicon::OutlinedDocumentText,
                'title' => 'فاکتورها و پیش‌فاکتورها',
                'subtitle' => 'فهرست فاکتورها و پیش‌فاکتورهای صادرشده',
                'breadcrumbs' => [
                    ['label' => 'فاکتورها و پیش‌فاکتورها', 'url' => null],
                ],
                'actions' => $this->getCachedHeaderActions(),
            ],
        ]);
    }
}
