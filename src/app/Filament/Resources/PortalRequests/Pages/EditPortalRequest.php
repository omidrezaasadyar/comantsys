<?php

namespace App\Filament\Resources\PortalRequests\Pages;

use App\Filament\Resources\PortalRequests\PortalRequestResource;
use App\Filament\Resources\PortalRequests\Schemas\PortalRequestForm;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

/**
 * The review/respond screen.
 *
 * Zone A (customer submission) is disabled in the schema, so Filament never
 * dehydrates it and none of those columns reach $data — nothing here has to
 * defend them a second time.
 *
 * What this page owns is the ADMIN half of the attachment split. The form key
 * `admin_attachments` is not a column on portal_requests (it is a hasMany), so
 * it is lifted out before save and reconciled against the related table after,
 * filtered to source='admin' on BOTH the read and the write side. Customer
 * rows (source='customer') are never read into the field and never touched by
 * the reconcile — an operator cannot delete the customer's evidence through
 * this screen, and their own files cannot appear in the customer download list.
 *
 * Same lift-out-then-write shape as CreatePortalRequest on the portal side.
 */
class EditPortalRequest extends EditRecord
{
    protected static string $resource = PortalRequestResource::class;

    /**
     * Paths submitted in the FileUpload, carried from mutateFormDataBeforeSave()
     * to afterSave().
     *
     * @var array<int, string>
     */
    protected array $adminFiles = [];

    /** portal_request_messages.sender for a message written on this screen. */
    public const SENDER_ADMIN = 'admin';

    /** The status the revision checkbox moves the request into. */
    public const STATUS_NEEDS_REVISION = 'needs_revision';

    /**
     * Post a message into the request thread.
     *
     * Append-only: it creates a row and never reads, updates or deletes an
     * existing one, which is what keeps the conversation an audit trail rather
     * than an editable field.
     *
     * The revision checkbox is deliberately ONE-WAY. Ticking it moves the
     * request to needs_revision (that is what unlocks editing on the portal
     * side); leaving it unticked does nothing at all — it never clears the
     * status, so an operator sending a routine follow-up cannot silently
     * re-lock a request that is already back with the customer.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendMessage')
                ->label(__('portal_requests.admin.action.send_message'))
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->modalHeading(__('portal_requests.admin.action.send_message'))
                ->modalSubmitActionLabel(__('portal_requests.admin.action.send_message_submit'))
                ->schema([
                    Textarea::make('body')
                        ->label(__('portal_requests.admin.field.message_body'))
                        ->rows(5)
                        ->required()
                        ->maxLength(5000),

                    Checkbox::make('ask_revision')
                        ->label(__('portal_requests.admin.field.ask_revision'))
                        ->helperText(__('portal_requests.admin.help.ask_revision'))
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    $this->record->messages()->create([
                        'sender' => self::SENDER_ADMIN,
                        'body' => $data['body'],
                    ]);

                    if (filled($data['ask_revision'] ?? null) && $data['ask_revision']) {
                        $this->record->request_status = self::STATUS_NEEDS_REVISION;

                        // saveQuietly: PortalRequest::booted() hooks on
                        // creating/created only, but a future hook must not be
                        // able to turn a message post into a cascade.
                        $this->record->saveQuietly();

                        // The open form still holds the PREVIOUS request_status
                        // in its Livewire state. Without this, the operator's
                        // next "Save" would write the stale value straight back
                        // over needs_revision.
                        $this->refreshFormData(['request_status']);
                    }

                    // Drop the cached relation so the Zone C Placeholder
                    // re-queries and the new message shows immediately.
                    $this->record->unsetRelation('messages');

                    Notification::make()
                        ->title(__('portal_requests.admin.notify.message_sent'))
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * Load the EXISTING admin files into the upload field so the operator sees
     * what was already sent, instead of an empty box that silently wipes them.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data[PortalRequestForm::ADMIN_ATTACHMENTS_KEY] = $this->record
            ->attachments()
            ->where('source', PortalRequestForm::SOURCE_ADMIN)
            ->orderBy('id')
            ->pluck('file_path')
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->adminFiles = array_values(array_filter(
            (array) ($data[PortalRequestForm::ADMIN_ATTACHMENTS_KEY] ?? []),
        ));

        // Not a column — leaving it in $data would blow up the UPDATE.
        unset($data[PortalRequestForm::ADMIN_ATTACHMENTS_KEY]);

        return $data;
    }

    /**
     * Reconcile the admin attachment rows with what the field now holds.
     *
     * The query is filtered to source='admin' before anything is compared, so
     * the "not in the submitted list ⇒ delete" branch can only ever reach the
     * admin's own rows.
     */
    protected function afterSave(): void
    {
        $existing = $this->record
            ->attachments()
            ->where('source', PortalRequestForm::SOURCE_ADMIN)
            ->get();

        // Removed in the UI ⇒ drop the row. The file itself is left on disk:
        // ->preserveFilenames() means two requests can legitimately point at the
        // same path, so unlinking here could break another record's download.
        foreach ($existing as $attachment) {
            if (! in_array($attachment->file_path, $this->adminFiles, true)) {
                $attachment->delete();
            }
        }

        $known = $existing->pluck('file_path')->all();

        foreach ($this->adminFiles as $path) {
            if (in_array($path, $known, true)) {
                continue;
            }

            $this->record->attachments()->create([
                'title' => pathinfo($path, PATHINFO_BASENAME),
                'file_path' => $path,
                'file_type' => static::mimeTypeOf($path),
                'source' => PortalRequestForm::SOURCE_ADMIN,
            ]);
        }
    }

    /**
     * file_type holds a MIME type. Storage can fail on an unreadable file, so
     * degrade to null rather than losing the whole save over metadata.
     */
    protected static function mimeTypeOf(string $path): ?string
    {
        try {
            return Storage::disk(PortalRequestForm::DISK)->mimeType($path) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
