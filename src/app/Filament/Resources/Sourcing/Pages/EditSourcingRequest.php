<?php

namespace App\Filament\Resources\Sourcing\Pages;

use App\Filament\Resources\Sourcing\SourcingRequestResource;
use App\Jobs\RunSourcingAgent;
use App\Models\SourcingRequest;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSourcingRequest extends EditRecord
{
    protected static string $resource = SourcingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->runAction(),
            DeleteAction::make(),
        ];
    }

    /**
     * «اجرای تأمین‌یابی» — queue a sourcing run for this request.
     * Opens a modal to pick the output language, guards against a run that is
     * already pending/running, then creates the pending run and dispatches.
     */
    protected function runAction(): Action
    {
        return Action::make('run')
            ->label(__('sourcing.action.run'))
            ->icon(Heroicon::OutlinedSparkles)
            ->schema([
                Select::make('language')
                    ->label(__('sourcing.field.output_language'))
                    ->options([
                        'fa' => __('sourcing.lang.fa'),
                        'en' => __('sourcing.lang.en'),
                    ])
                    ->default(config('sourcing.agent.output_language'))
                    ->required(),
            ])
            ->action(function (array $data): void {
                /** @var SourcingRequest $request */
                $request = $this->getRecord();

                // Guard: never queue a second run while one is pending/running.
                $alreadyRunning = $request->runs()
                    ->whereIn('status', ['pending', 'running'])
                    ->exists();

                if ($alreadyRunning) {
                    Notification::make()
                        ->title(__('sourcing.notify.in_progress_title'))
                        ->warning()
                        ->send();

                    return;
                }

                $run = $request->runs()->create(['status' => 'pending']);

                RunSourcingAgent::dispatch($run, ['language' => $data['language']]);

                Notification::make()
                    ->title(__('sourcing.notify.queued_title'))
                    ->body(__('sourcing.notify.queued_body'))
                    ->success()
                    ->send();
            });
    }
}
