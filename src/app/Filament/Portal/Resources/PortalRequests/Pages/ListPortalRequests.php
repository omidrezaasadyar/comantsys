<?php

namespace App\Filament\Portal\Resources\PortalRequests\Pages;

use App\Filament\Portal\Resources\PortalRequests\PortalRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPortalRequests extends ListRecords
{
    protected static string $resource = PortalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('portal_requests.create')),
        ];
    }
}
