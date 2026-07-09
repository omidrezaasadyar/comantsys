<?php

namespace App\Filament\Resources\Sourcing\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SourcingRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('sourcing.section.info'))
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                TextInput::make('part_name')
                                    ->label(__('sourcing.field.part_name'))
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('part_number')
                                    ->label(__('sourcing.field.part_number'))
                                    ->maxLength(255),
                            ]),

                        Textarea::make('description')
                            ->label(__('sourcing.field.description'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('search_instructions')
                            ->label(__('sourcing.field.search_instructions'))
                            ->helperText(__('sourcing.help.search_instructions'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Select::make('status')
                            ->label(__('sourcing.field.request_status'))
                            ->options([
                                'active'   => __('sourcing.request_status.active'),
                                'archived' => __('sourcing.request_status.archived'),
                            ])
                            ->default('active')
                            ->required()
                            ->native(false),
                    ]),
            ]);
    }
}
