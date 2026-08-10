<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات کاربر')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('نام')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('ایمیل')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('رمز عبور')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->helperText('در حالت ویرایش، خالی بگذارید تا رمز فعلی حفظ شود.')
                            ->maxLength(255),
                    ]),

                Section::make('دسترسی')
                    ->schema([
                        Select::make('roles')
                            ->label('نقش‌ها')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
