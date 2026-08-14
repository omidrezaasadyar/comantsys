<?php

namespace App\Filament\Resources\PartnerTransactions\Pages;

use App\Filament\Resources\PartnerTransactions\PartnerTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class ListPartnerTransactions extends ListRecords
{
    protected static string $resource = PartnerTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.records.partials.header-band', [
            'header' => [
                'icon' => Heroicon::OutlinedBanknotes,
                'title' => 'تراکنش‌های مالی',
                'subtitle' => 'دریافت و پرداخت‌های ثبت‌شدهٔ طرف‌حساب‌ها',
                'breadcrumbs' => [
                    ['label' => 'تراکنش‌های مالی', 'url' => null],
                ],
                'actions' => $this->getCachedHeaderActions(),
            ],
        ]);
    }
}
