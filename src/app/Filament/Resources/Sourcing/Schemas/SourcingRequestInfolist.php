<?php

namespace App\Filament\Resources\Sourcing\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SourcingRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('sourcing.section.info'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('part_name')->label(__('sourcing.field.part_name')),
                        TextEntry::make('part_number')->label(__('sourcing.field.part_number')),
                        TextEntry::make('description')
                            ->label(__('sourcing.field.description'))
                            ->columnSpanFull(),
                        TextEntry::make('search_instructions')
                            ->label(__('sourcing.field.search_instructions'))
                            ->columnSpanFull(),
                        TextEntry::make('status')
                            ->label(__('sourcing.field.request_status'))
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'active'   => __('sourcing.request_status.active'),
                                'archived' => __('sourcing.request_status.archived'),
                                default    => '—',
                            }),
                    ]),
            ]);
    }
}
