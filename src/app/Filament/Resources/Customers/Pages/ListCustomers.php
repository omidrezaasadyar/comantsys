<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Same mechanism as the Sale list: Filament's page wrapper renders
     * `{{ $this->getHeader() }}` INSTEAD of its default header once this
     * returns a view — the heading, breadcrumbs and header actions in the
     * `@else` branch are skipped entirely
     * (filament/resources/views/components/page/index.blade.php:45-77). So
     * nothing is duplicated, provided the band renders the actions itself:
     * getCachedHeaderActions() hands over the already-booted CreateAction
     * above, keeping its «ایجاد مشتری» label, icon and authorization.
     */
    public function getHeader(): ?View
    {
        return view('filament.records.partials.header-band', [
            'header' => [
                'icon' => Heroicon::OutlinedUsers,
                'title' => 'مشتریان',
                'subtitle' => 'فهرست مشتریان ثبت‌شده',
                'breadcrumbs' => [
                    ['label' => 'مشتریان', 'url' => null],
                ],
                'actions' => $this->getCachedHeaderActions(),
            ],
        ]);
    }
}
