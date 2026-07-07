<?php

namespace App\Filament\Resources\Sourcing\RelationManagers;

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
use Illuminate\Database\Eloquent\Model;

/**
 * Attachments for a sourcing request — same pattern as the Inquiries module:
 * one row per file on the private `local` disk, downloaded through an
 * auth-gated route (never a public URL).
 */
class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('sourcing.section.attachments');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('title')
                    ->label(__('sourcing.field.attachment_title'))
                    ->maxLength(255),

                FileUpload::make('file_path')
                    ->label(__('sourcing.field.file'))
                    ->helperText(__('sourcing.help.file'))
                    ->required()
                    ->disk('local')
                    ->directory('sourcing-request-attachments')
                    ->preserveFilenames()
                    ->maxSize(10240)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label(__('sourcing.field.attachment_title'))
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('file_path')
                    ->label(__('sourcing.field.file'))
                    ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : '—')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label(__('sourcing.field.uploaded_at'))
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('sourcing.add_attachment')),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('sourcing.open'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record): string => route('sourcing-request-attachment.download', $record))
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
