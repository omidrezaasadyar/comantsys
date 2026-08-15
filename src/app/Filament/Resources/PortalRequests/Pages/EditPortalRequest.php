<?php

namespace App\Filament\Resources\PortalRequests\Pages;

use App\Filament\Resources\PortalRequests\PortalRequestResource;
use App\Filament\Resources\PortalRequests\Schemas\PortalRequestForm;
use Filament\Resources\Pages\EditRecord;
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
