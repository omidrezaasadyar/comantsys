<?php

namespace App\Filament\Resources\PartnerTransactions\Pages;

use App\Filament\Resources\PartnerTransactions\PartnerTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPartnerTransaction extends EditRecord
{
    protected static string $resource = PartnerTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
