<?php

namespace App\Filament\Resources\PortalRequests\Tables;

use App\Models\PortalRequest;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Admin list of portal requests — the review queue.
 *
 * Unlike the portal's own table this one is unscoped (every customer's rows)
 * and carries no create action: requests are born in the portal.
 */
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
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('requester_name')
                    ->label(__('portal_requests.field.requester_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company')
                    ->label(__('portal_requests.field.company'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subject')
                    ->label(__('portal_requests.field.subject'))
                    ->searchable()
                    ->wrap(),

                // Label + colour maps live on the model, same wiring as the
                // portal table and InquiriesTable.
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

                // میلادی در دیتابیس، شمسی روی صفحه — سمت ادمین همیشه شمسی است.
                TextColumn::make('request_date')
                    ->label(__('portal_requests.field.request_date'))
                    ->jalaliDate()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('validation_status')
                    ->label(__('portal_requests.field.validation_status'))
                    ->options(PortalRequest::validationStatuses()),

                SelectFilter::make('request_status')
                    ->label(__('portal_requests.field.request_status'))
                    ->options(PortalRequest::requestStatuses()),
            ])
            ->recordActions([
                // Opens the review screen. No ViewAction (no view page yet) and
                // no DeleteAction — a customer's submission is not admin litter.
                EditAction::make()
                    ->label(__('portal_requests.admin.review')),
            ]);
    }
}
