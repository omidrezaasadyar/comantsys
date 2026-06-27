<?php
namespace App\Filament\Resources\Suppliers\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = "attachments";

    protected static ?string $title = "پیوست‌ها";

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make("title")
                    ->label("عنوان")
                    ->maxLength(255),
                FileUpload::make("file_path")
                    ->label("فایل")
                    ->required()
                    ->disk("public")
                    ->directory("supplier-attachments")
                    ->downloadable()
                    ->openable()
                    ->maxSize(10240),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute("title")
            ->columns([
                TextColumn::make("title")
                    ->label("عنوان")
                    ->placeholder("بدون عنوان")
                    ->searchable(),
                TextColumn::make("file_path")
                    ->label("فایل")
                    ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : "-")
                    ->url(fn ($record): ?string => $record->file_path ? asset("storage/" . $record->file_path) : null, true),
                TextColumn::make("created_at")
                    ->label("تاریخ بارگذاری")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->label("افزودن پیوست"),
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
