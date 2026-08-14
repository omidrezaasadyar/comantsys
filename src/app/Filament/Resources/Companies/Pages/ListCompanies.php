<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Same mechanism as the Sale, Customer and Inquiry lists: Filament's page
     * wrapper renders `{{ $header }}` INSTEAD of its default header once
     * getHeader() returns a view — the heading, breadcrumbs and header actions
     * in the `@else` branch are skipped entirely
     * (filament/resources/views/components/page/index.blade.php:45-77), so the
     * default heading is never printed twice. The band renders the actions
     * itself: getCachedHeaderActions() hands over the already-booted
     * CreateAction above, keeping its «ایجاد شرکت» label, icon and
     * authorization.
     *
     * The icon matches the one ViewCompany puts on its own band.
     */
    public function getHeader(): ?View
    {
        return view('filament.records.partials.header-band', [
            'header' => [
                'icon' => Heroicon::OutlinedBuildingOffice2,
                'title' => 'شرکت‌های فروشنده',
                'subtitle' => 'فهرست شرکت‌های فروشنده',
                'breadcrumbs' => [
                    ['label' => 'شرکت‌های فروشنده', 'url' => null],
                ],
                'actions' => $this->getCachedHeaderActions(),
            ],
        ]);
    }
}
