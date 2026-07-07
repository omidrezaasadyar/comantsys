<?php

namespace App\Filament\Resources\Sourcing\Pages;

use App\Filament\Resources\Sourcing\SourcingRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSourcingRequests extends ListRecords
{
    protected static string $resource = SourcingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
