<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (($data['locale'] ?? 'fa') === 'fa') {
            $data['invoice_date_jalali'] = InvoiceForm::gregorianToJalali($data['invoice_date'] ?? null);
            $data['inquiry_date_jalali'] = InvoiceForm::gregorianToJalali($data['inquiry_date'] ?? null);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['locale'] ?? 'fa') === 'fa') {
            $data['invoice_date'] = InvoiceForm::jalaliToGregorian($data['invoice_date_jalali'] ?? null);
            $data['inquiry_date'] = InvoiceForm::jalaliToGregorian($data['inquiry_date_jalali'] ?? null);
        }

        if (empty($data['invoice_date'])) {
            $data['invoice_date'] = now()->format('Y-m-d');
        }

        unset($data['invoice_date_jalali'], $data['inquiry_date_jalali']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->calculateTotals();
        $this->record->saveQuietly();
    }
}