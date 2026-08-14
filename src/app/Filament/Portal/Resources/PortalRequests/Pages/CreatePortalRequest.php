<?php

namespace App\Filament\Portal\Resources\PortalRequests\Pages;

use App\Filament\Portal\Resources\PortalRequests\PortalRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreatePortalRequest extends CreateRecord
{
    protected static string $resource = PortalRequestResource::class;

    /**
     * Uploaded file paths, lifted out of the form data in
     * mutateFormDataBeforeCreate() and written to the related table in
     * afterCreate() — once the parent row (and its id) exists.
     *
     * @var array<int, string>
     */
    protected array $uploadedFiles = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 'attachments' is a hasMany relation, not a column on portal_requests.
        // Pull it out before the row is filled, and keep it for afterCreate().
        $this->uploadedFiles = array_values(array_filter((array) ($data['attachments'] ?? [])));
        unset($data['attachments']);

        // Server-set, never trusted from the form.
        $data['user_id'] = auth()->id();
        $data['request_date'] = today();

        // NOTE: request_number is deliberately NOT set here — PortalRequest's
        // creating/created hooks generate it from the new id.

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->uploadedFiles as $path) {
            $this->record->attachments()->create([
                'title' => pathinfo($path, PATHINFO_BASENAME),
                'file_path' => $path,
                'file_type' => static::mimeTypeOf($path),
                'source' => 'customer',
            ]);
        }
    }

    /**
     * file_type holds a MIME type (SourcingAgentService reads it as one).
     * Storage can fail on an unreadable file, so degrade to null rather than
     * losing the whole request over a metadata lookup.
     */
    protected static function mimeTypeOf(string $path): ?string
    {
        try {
            return Storage::disk('local')->mimeType($path) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
