<?php
namespace App\Filament\Resources\Suppliers\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PartsRelationManager extends RelationManager
{
    protected static string $relationship = "parts";

    protected static ?string $title = "قطعات و قیمت";

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make("part_name")
                    ->label("نام قطعه")
                    ->required()
                    ->maxLength(255),
                TextInput::make("part_number")
                    ->label("کد فنی")
                    ->maxLength(255),
                TextInput::make("price")
                    ->label("قیمت")
                    ->numeric()
                    ->minValue(0),
                Select::make("currency")
                    ->label("واحد پول")
                    ->options([
                        "IRR" => "ریال",
                        "EUR" => "یورو",
                        "GBP" => "پوند",
                        "USD" => "دلار",
                    ])
                    ->default("IRR")
                    ->required(),
                Textarea::make("notes")
                    ->label("توضیحات")
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute("part_name")
            ->columns([
                TextColumn::make("part_name")
                    ->label("نام قطعه")
                    ->searchable(),
                TextColumn::make("part_number")
                    ->label("کد فنی")
                    ->searchable(),
                TextColumn::make("price")
                    ->label("قیمت")
                    ->numeric()
                    ->sortable(),
                TextColumn::make("currency")
                    ->label("واحد پول")
                    ->badge(),
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
                    ->label("افزودن قطعه"),
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
