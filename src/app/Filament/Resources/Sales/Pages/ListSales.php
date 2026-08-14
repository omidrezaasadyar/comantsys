<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Filament's page wrapper renders `{{ $this->getHeader() }}` INSTEAD of its
     * default header when this returns a view — the heading, breadcrumbs and
     * header actions in the `@else` branch are skipped entirely
     * (filament/resources/views/components/page/index.blade.php:45-77). So
     * nothing is duplicated, provided the band renders the actions itself:
     * getCachedHeaderActions() hands over the already-booted CreateAction
     * above, keeping its label, icon and authorization.
     */
    public function getHeader(): ?View
    {
        return view('filament.records.partials.header-band', [
            'header' => [
                'icon' => Heroicon::OutlinedShoppingCart,
                'title' => 'فروش‌ها',
                'subtitle' => 'فهرست معاملات ثبت‌شده',
                'breadcrumbs' => [
                    ['label' => 'فروش‌ها', 'url' => null],
                ],
                'actions' => $this->getCachedHeaderActions(),
            ],
        ]);
    }
}
