<?php

namespace App\Filament\Resources\PortalRequests\Pages;

use App\Filament\Resources\PortalRequests\PortalRequestResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Admin review queue. No header actions on purpose — there is no create page,
 * because requests come in from the customer portal.
 */
class ListPortalRequests extends ListRecords
{
    protected static string $resource = PortalRequestResource::class;
}
