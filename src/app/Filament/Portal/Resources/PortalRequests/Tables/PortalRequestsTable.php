<?php

namespace App\Filament\Portal\Resources\PortalRequests\Tables;

use App\Models\PortalRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PortalRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('request_date', 'desc')
            ->columns([
                TextColumn::make('request_number')
                    ->label(__('portal_requests.field.request_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subject')
                    ->label(__('portal_requests.field.subject'))
                    ->searchable()
                    ->wrap(),

                // The model already exposes the label/colour maps, so wiring
                // them is a one-liner each — same shape as InquiriesTable.
                TextColumn::make('validation_status')
                    ->label(__('portal_requests.field.validation_status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PortalRequest::validationStatuses()[$state] ?? $state)
                    ->color(fn (string $state): string => PortalRequest::validationStatusColors()[$state] ?? 'gray')
                    ->sortable(),

                TextColumn::make('request_status')
                    ->label(__('portal_requests.field.request_status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PortalRequest::requestStatuses()[$state] ?? $state)
                    ->color(fn (string $state): string => PortalRequest::requestStatusColors()[$state] ?? 'gray')
                    ->sortable(),

                TextColumn::make('request_date')
                    ->label(__('portal_requests.field.request_date'))
                    ->jalaliDate()
                    ->sortable(),
            ]);
    }
}
