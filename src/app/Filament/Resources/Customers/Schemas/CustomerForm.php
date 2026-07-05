<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات اصلی')
                    ->columns(2)
                    ->schema([
                        Select::make('person_type')
                            ->label('نوع شخص')
                            ->options([
                                'legal' => 'حقوقی',
                                'real'  => 'حقیقی',
                            ])
                            ->default('legal')
                            ->required(),

                        TextInput::make('name')
                            ->label('نام / شرکت')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('national_id')
                            ->label('شناسه ملی / کد ملی')
                            ->maxLength(255),

                        TextInput::make('economic_code')
                            ->label('کد اقتصادی')
                            ->maxLength(255),
                    ]),

                Section::make('اطلاعات تماس')
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')
                            ->label('تلفن')
                            ->tel()
                            ->maxLength(255),

                        TextInput::make('postal_code')
                            ->label('کد پستی')
                            ->maxLength(255),

                        Textarea::make('address')
                            ->label('آدرس')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('سایر')
                    ->schema([
                        Textarea::make('notes')
                            ->label('یادداشت')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
