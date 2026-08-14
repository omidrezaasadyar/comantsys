<?php

namespace App\Livewire\Partner;

use App\Filament\Pages\PartnerTransactionView;
use App\Models\PartnerTransaction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class TransactionsTab extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    /**
     * Balance per currency.
     * paid_in / paid_out = totals of PAID contributions / withdrawals.
     * realized = paid_in - paid_out. Pending amounts are separate.
     *
     * @return array<string, array{paid_in: float, paid_out: float, realized: float, pending_in: float, pending_out: float}>
     */
    public function getBalances(): array
    {
        $rows = PartnerTransaction::query()
            ->where('user_id', auth()->id())
            ->selectRaw('currency, type, status, SUM(amount) as total')
            ->groupBy('currency', 'type', 'status')
            ->get();

        $balances = [];

        foreach ($rows as $row) {
            $currency = $row->currency;
            $amount = (float) $row->total;

            $balances[$currency] ??= [
                'paid_in' => 0.0,
                'paid_out' => 0.0,
                'realized' => 0.0,
                'pending_in' => 0.0,
                'pending_out' => 0.0,
            ];

            if ($row->status === 'paid') {
                if ($row->type === 'contribution') {
                    $balances[$currency]['paid_in'] += $amount;
                } else {
                    $balances[$currency]['paid_out'] += $amount;
                }
            } elseif ($row->type === 'contribution') {
                $balances[$currency]['pending_in'] += $amount;
            } else {
                $balances[$currency]['pending_out'] += $amount;
            }
        }

        foreach ($balances as $currency => $b) {
            $balances[$currency]['realized'] = $b['paid_in'] - $b['paid_out'];
        }

        return $balances;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => PartnerTransaction::query()->where('user_id', auth()->id()))
            ->defaultSort('txn_date', 'desc')
            // Clicking a row opens the read-only detail view. The URL is only
            // the way in — PartnerTransactionView::mount() re-resolves the
            // record against the signed-in user and 404s on anyone else's row,
            // so this link is convenience, not the access check.
            // Pass the primary key, not the model: route() would bind a model
            // through getRouteKey() anyway, but naming the key here leaves
            // nothing for the binder to infer.
            ->recordUrl(fn (PartnerTransaction $record): string => PartnerTransactionView::getUrl(['record' => $record->getKey()]))
            ->columns([
                TextColumn::make('type')
                    ->label('نوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PartnerTransaction::types()[$state] ?? $state)
                    ->color(fn (string $state): string => PartnerTransaction::typeColors()[$state] ?? 'gray'),

                TextColumn::make('amount')
                    ->label('مبلغ')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PartnerTransaction::statuses()[$state] ?? $state)
                    ->color(fn (string $state): string => PartnerTransaction::statusColors()[$state] ?? 'gray'),

                TextColumn::make('payment_method')
                    ->label('روش پرداخت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (PartnerTransaction::paymentMethods()[$state] ?? $state) : '—')
                    ->color(fn (?string $state): string => match ($state) {
                        'bank' => 'info',
                        'cash' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('txn_date')
                    ->label('تاریخ پرداخت')
                    ->jalaliDate()
                    ->sortable(),

                TextColumn::make('purpose')
                    ->label('بابت')
                    ->limit(40)
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('نوع')
                    ->options(PartnerTransaction::types()),

                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(PartnerTransaction::statuses()),
            ]);
    }

    public function render(): View
    {
        return view('livewire.partner.transactions-tab');
    }
}
