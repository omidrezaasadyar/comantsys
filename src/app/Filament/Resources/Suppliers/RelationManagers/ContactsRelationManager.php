<?php
namespace App\Filament\Resources\Suppliers\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = "contacts";

    protected static ?string $title = "افراد تماس";

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make("name")
                    ->label("نام")
                    ->required()
                    ->maxLength(255),
                TextInput::make("position")
                    ->label("سمت")
                    ->maxLength(255),
                TextInput::make("phone")
                    ->label("تلفن")
                    ->tel()
                    ->maxLength(255),
                TextInput::make("mobile")
                    ->label("موبایل")
                    ->maxLength(255),
                TextInput::make("email")
                    ->label("ایمیل")
                    ->email()
                    ->maxLength(255),
                Toggle::make("is_primary")
                    ->label("تماس اصلی")
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute("name")
            ->columns([
                TextColumn::make("name")
                    ->label("نام")
                    ->searchable(),
                TextColumn::make("position")
                    ->label("سمت")
                    ->searchable(),
                TextColumn::make("phone")
                    ->label("تلفن")
                    ->searchable(),
                TextColumn::make("mobile")
                    ->label("موبایل")
                    ->searchable(),
                TextColumn::make("email")
                    ->label("ایمیل")
                    ->searchable(),
                IconColumn::make("is_primary")
                    ->label("تماس اصلی")
                    ->boolean(),
                TextColumn::make("created_at")
                    ->label("تاریخ ایجاد")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("updated_at")
                    ->label("آخرین بروزرسانی")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->label("افزودن فرد تماس"),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
