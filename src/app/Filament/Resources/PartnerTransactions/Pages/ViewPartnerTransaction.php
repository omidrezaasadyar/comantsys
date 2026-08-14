<?php

namespace App\Filament\Resources\PartnerTransactions\Pages;

use App\Filament\Concerns\HasRecordPageLayout;
use App\Filament\Resources\PartnerTransactions\PartnerTransactionResource;
use App\Filament\Resources\PartnerTransactions\Schemas\PartnerTransactionInfolist;
use App\Models\PartnerTransaction;
use Filament\Resources\Pages\ViewRecord;

class ViewPartnerTransaction extends ViewRecord
{
    use HasRecordPageLayout;

    protected static string $resource = PartnerTransactionResource::class;

    /**
     * Same contract ViewSale implements. The mapping itself lives in
     * PartnerTransactionInfolist so the Schemas/ namespace stays the one place
     * a field is added or relabelled.
     *
     * @return array<string, mixed>
     */
    protected function getRecordPageSchema(): array
    {
        /** @var PartnerTransaction $txn */
        $txn = $this->getRecord();

        return PartnerTransactionInfolist::schema($txn);
    }
}
