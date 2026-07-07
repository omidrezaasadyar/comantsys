<?php

namespace App\Filament\Resources\Inquiries\Pages;

use App\Filament\Resources\Inquiries\InquiryResource;
use App\Jobs\RunSourcingAgent;
use App\Models\Inquiry;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditInquiry extends EditRecord
{
    protected static string $resource = InquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->sourcingAction(),
            DeleteAction::make(),
        ];
    }

    /**
     * «تأمین‌یابی هوشمند» — dispatch the AI sourcing agent for this inquiry.
     * Opens a modal to pick the output language, guards against a run that is
     * already pending/running, then creates the row and queues the job.
     */
    protected function sourcingAction(): Action
    {
        return Action::make('sourcing')
            ->label(__('inquiries.action.sourcing'))
            ->icon(Heroicon::OutlinedSparkles)
            ->schema([
                Select::make('language')
                    ->label(__('inquiries.field.output_language'))
                    ->options([
                        'fa' => __('inquiries.lang.fa'),
                        'en' => __('inquiries.lang.en'),
                    ])
                    ->default(config('sourcing.agent.output_language'))
                    ->required(),
            ])
            ->action(function (array $data): void {
                /** @var Inquiry $inquiry */
                $inquiry = $this->getRecord();

                // Guard: never dispatch a second run while one is pending/running
                // — prevents duplicate work and duplicate paid API spend.
                $alreadyRunning = $inquiry->sourcingResults()
                    ->whereIn('status', ['pending', 'running'])
                    ->exists();

                if ($alreadyRunning) {
                    Notification::make()
                        ->title(__('inquiries.notify.in_progress_title'))
                        ->warning()
                        ->send();

                    return;
                }

                $result = $inquiry->sourcingResults()->create(['status' => 'pending']);

                RunSourcingAgent::dispatch($result, ['language' => $data['language']]);

                Notification::make()
                    ->title(__('inquiries.notify.queued_title'))
                    ->body(__('inquiries.notify.queued_body'))
                    ->success()
                    ->send();
            });
    }
}
