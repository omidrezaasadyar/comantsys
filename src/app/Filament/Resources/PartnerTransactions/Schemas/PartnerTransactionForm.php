<?php

namespace App\Filament\Resources\PartnerTransactions\Schemas;

use App\Models\PartnerTransaction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PartnerTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات تراکنش')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('user_id')
                            ->label('طرف حساب')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereHas(
                                    'roles',
                                    fn ($q) => $q->where('name', 'partner'),
                                ),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('type')
                            ->label('نوع تراکنش')
                            ->options(PartnerTransaction::types())
                            ->required()
                            ->live(),

                        Select::make('status')
                            ->label('وضعیت')
                            ->options(PartnerTransaction::statuses())
                            ->default('paid')
                            ->required(),

                        TextInput::make('amount')
                            ->label('مبلغ')
                            ->numeric()
                            ->minValue(0)
                            ->required(),

                        Select::make('currency')
                            ->label('ارز')
                            ->options([
                                'IRR' => 'ریال (IRR)',
                                'EUR' => 'یورو (EUR)',
                                'GBP' => 'پوند (GBP)',
                                'USD' => 'دلار (USD)',
                            ])
                            ->default('IRR')
                            ->required(),

                        DatePicker::make('txn_date')
                            ->label('تاریخ پرداخت')
                            ->jalali()
                            ->required(),

                        Radio::make('payment_method')
                            ->label('روش پرداخت')
                            ->options(PartnerTransaction::paymentMethods())
                            ->default('bank')
                            ->required()
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('account_holder')
                            ->label('نام صاحب حساب')
                            ->visible(fn (Get $get) => $get('payment_method') === 'bank'),

                        TextInput::make('account_number')
                            ->label('شماره حساب / شبا')
                            ->visible(fn (Get $get) => $get('payment_method') === 'bank'),

                        Textarea::make('purpose')
                            ->label('بابت')
                            ->placeholder('این پرداخت بابت چه بوده است؟')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
