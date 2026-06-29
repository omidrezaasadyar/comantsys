<?php

namespace App\Filament\Resources\Sales\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'مستندات';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('file')
                    ->label('فایل')
                    ->helperText('حداکثر حجم مجاز: ۱۰ مگابایت — فرمت‌های مجاز: PDF، تصویر، Word، Excel')
                    ->disk('public')
                    ->directory('sale-attachments')
                    ->downloadable()
                    ->openable()
                    ->maxSize(10240)
                    ->preserveFilenames()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('file')
            ->columns([
                TextColumn::make('file')
                    ->label('نام فایل')
                    ->formatStateUsing(fn ($state) => basename($state))
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('تاریخ آپلود')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('افزودن مستند'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('باز کردن')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => Storage::disk('public')->url($record->file))
                    ->openUrlInNewTab(),
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
