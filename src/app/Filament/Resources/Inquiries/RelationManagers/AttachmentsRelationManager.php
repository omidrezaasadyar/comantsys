<?php

namespace App\Filament\Resources\Inquiries\RelationManagers;

use Filament\Actions\Action;
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
    protected static string $relationship = 'attachments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('inquiries.section.attachments');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('title')
                    ->label(__('inquiries.field.attachment_title'))
                    ->maxLength(255),

                // دیسک خصوصی (local = storage/app/private)، با حفظ نام اصلی فایل
                FileUpload::make('file_path')
                    ->label(__('inquiries.field.file'))
                    ->helperText(__('inquiries.help.file'))
                    ->required()
                    ->disk('local')
                    ->directory('inquiry-attachments')
                    ->preserveFilenames()
                    ->maxSize(10240)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label(__('inquiries.field.attachment_title'))
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('file_path')
                    ->label(__('inquiries.field.file'))
                    ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : '—')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label(__('inquiries.field.uploaded_at'))
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('inquiries.add_attachment')),
            ])
            ->recordActions([
                // دانلود امن از مسیر auth-gated (نه URL عمومی)
                Action::make('open')
                    ->label(__('inquiries.open'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record): string => route('inquiry-attachment.download', $record))
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
