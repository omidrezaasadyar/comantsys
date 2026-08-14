<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Filament\Actions\DeleteSelectedBulkAction;
use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\Response;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('لوگو')
                    ->circular(),

                TextColumn::make('name')
                    ->label('نام شرکت')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('prefix')
                    ->label('پیشوند')
                    ->badge()
                    ->color('info'),

                TextColumn::make('locale')
                    ->label('زبان')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'fa' => 'فارسی',
                        'en' => 'انگلیسی',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'fa' ? 'success' : 'warning'),

                TextColumn::make('default_currency')
                    ->label('ارز پیش‌فرض')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'IRR' => 'ریال',
                        'EUR' => 'یورو',
                        'USD' => 'دلار',
                        'GBP' => 'پوند',
                        default => $state,
                    }),

                TextColumn::make('phone')
                    ->label('تلفن')
                    ->toggleable(),

                TextColumn::make('national_id')
                    ->label('شناسه ملی')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->dateTime('Y/m/d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->before(function ($record, $action) {
                        // Invoice no longer uses SoftDeletes, so withTrashed()
                        // would throw BadMethodCallException here.
                        if ($record->invoices()->exists()) {
                            Notification::make()
                                ->title('امکان حذف وجود ندارد')
                                ->body('این شرکت دارای فاکتور است و قابل حذف نیست. ابتدا فاکتورهای مرتبط را مدیریت کنید.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->toolbarActions([
                // Guarded resource: a company that has invoices must not be
                // deleted. The authoritative guard stays on the model
                // (Company::booted() → deleting → CompanyHasInvoicesException);
                // this per-record authorization runs the same check up front so
                // guarded rows are skipped with a readable Persian reason
                // instead of surfacing as anonymous processing failures.
                // Failing per row — not cancelling the whole batch — is
                // deliberate: unguarded companies in the same selection are
                // still deleted.
                DeleteSelectedBulkAction::make()
                    ->authorizeIndividualRecords(function (Company $record): Response {
                        $response = CompanyResource::getDeleteAuthorizationResponse($record);

                        if (! $response->allowed()) {
                            return $response;
                        }

                        return $record->invoices()->exists()
                            ? Response::deny('«' . $record->name . '» دارای فاکتور است و قابل حذف نیست.')
                            : Response::allow();
                    }),
            ]);
    }
}
