<?php

namespace App\Filament\Widgets;

use App\Services\DatabaseBackupService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DatabaseBackupWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.widgets.database-backup-widget';

    protected static ?int $sort = -3;

    protected int | string | array $columnSpan = 1;

    public function backupAction(): Action
    {
        return Action::make('backup')
            ->label(__('dashboard.backup_db'))
            ->icon(Heroicon::ArrowDownTray)
            ->color('primary')
            ->iconButton()
            ->tooltip(__('dashboard.backup_db'))
            ->requiresConfirmation()
            ->modalHeading(__('dashboard.backup_db'))
            ->action(function () {
                try {
                    $file = app(DatabaseBackupService::class)->backup();
                    Notification::make()
                        ->title(__('dashboard.backup_success'))
                        ->body(basename($file))
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title(__('dashboard.backup_failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function restoreAction(): Action
    {
        return Action::make('restore')
            ->label(__('dashboard.restore_db'))
            ->icon(Heroicon::ArrowUpTray)
            ->color('danger')
            ->iconButton()
            ->tooltip(__('dashboard.restore_db'))
            ->modalHeading(__('dashboard.restore_db'))
            ->form([
                \Filament\Forms\Components\Select::make('backup_file')
                    ->label(__('dashboard.restore_file'))
                    ->options(fn () => app(DatabaseBackupService::class)->listBackups())
                    ->required()
                    ->native(false)
                    ->searchable(),
                TextInput::make('admin_password')
                    ->label(__('dashboard.admin_password'))
                    ->password()
                    ->required(),
            ])
            ->action(function (array $data) {
                if (! Hash::check($data['admin_password'], Auth::user()->password)) {
                    throw ValidationException::withMessages([
                        'admin_password' => __('dashboard.wrong_password'),
                    ]);
                }

                try {
                    $path = app(DatabaseBackupService::class)
                        ->pathForFilename($data['backup_file']);

                    app(DatabaseBackupService::class)->restore($path);

                    Notification::make()
                        ->title(__('dashboard.restore_success'))
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title(__('dashboard.restore_failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
