<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['locale'] ?? 'fa') === 'fa') {
            $data['invoice_date'] = InvoiceForm::jalaliToGregorian($data['invoice_date_jalali'] ?? null);
            $data['inquiry_date'] = InvoiceForm::jalaliToGregorian($data['inquiry_date_jalali'] ?? null);
        }

        if (empty($data['invoice_date'])) {
            $data['invoice_date'] = now()->format('Y-m-d');
        }

        // فیلدهای کمکی شمسی نباید به دیتابیس بروند
        unset($data['invoice_date_jalali'], $data['inquiry_date_jalali']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->calculateTotals();
        $this->record->saveQuietly();
    }
}