<?php

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

/**
 * Task form.
 * due_date uses the project's Jalali DatePicker (DB stores Gregorian).
 * The assignee Select is shown to anyone holding Create:Task — the same set
 * TaskPolicy::create() admits — so any permitted user may assign work. Its
 * required() is gated on the same check to stop a hidden field from failing
 * validation. No default(): the creator must consciously pick an assignee.
 */
class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('موضوع پیگیری')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('user_id')
                    ->label('واگذار به')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => auth()->user()?->can('Create:Task') ?? false)
                    ->required(fn (): bool => auth()->user()?->can('Create:Task') ?? false),

                DatePicker::make('due_date')
                    ->label('تاریخ سررسید')
                    ->jalali()
                    ->required(),

                Toggle::make('is_done')
                    ->label('انجام شد')
                    ->default(false)
                    ->inline(false),

                TextInput::make('completion_note')
                    ->label('توضیح')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
}
