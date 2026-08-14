<?php

namespace App\Filament\Portal\Resources\PortalRequests\Pages;

use App\Filament\Portal\Resources\PortalRequests\PortalRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListPortalRequests extends ListRecords
{
    protected static string $resource = PortalRequestResource::class;

    /**
     * No header actions: there is no create page yet, so a CreateAction here
     * would route to a page that does not exist.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
