<?php

namespace App\Filament\Resources\Sourcing\Pages;

use App\Filament\Resources\Sourcing\SourcingRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSourcingRequest extends ViewRecord
{
    protected static string $resource = SourcingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
