<?php

namespace App\Filament\Portal\Resources\PortalRequests\Pages;

use App\Filament\Portal\Resources\PortalRequests\PortalRequestResource;
use App\Models\PortalRequest;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

/**
 * The customer's revision screen — the ONLY way a submitted request can be
 * changed from the portal, and only while staff are waiting for that change.
 *
 * ── Two independent gates ──
 *
 *  a) OWNER SCOPE. Inherited, not re-implemented: EditRecord::resolveRecord()
 *     goes through Resource::resolveRecordRouteBinding() →
 *     getRecordRouteBindingEloquentQuery() → PortalRequestResource::
 *     getEloquentQuery(), which appends ->where('user_id', auth()->id()).
 *     Another customer's id therefore finds no row and 404s.
 *
 *  b) STATUS. Enforced in canAccess() below rather than by hiding a button,
 *     because a button is not a gate. Filament calls canAccess() from
 *     mountCanAuthorizeAccess() on first load AND from hydrateCanAuthorizeAccess()
 *     on every subsequent Livewire request (Resources/Pages/Concerns/
 *     InteractsWithRecord.php:19-27), aborting 403 when it returns false. So the
 *     window closes mid-session the moment the status moves on.
 *
 * ── What the customer may touch ──
 * Exactly the create form's fields, reused wholesale via the resource's form()
 * so the two can never drift apart in fields or validation. validation_status,
 * request_status and admin_response are not in that schema, so Filament never
 * dehydrates them; they are additionally stripped in mutateFormDataBeforeSave()
 * so a hand-made Livewire payload cannot smuggle them in either.
 *
 * Attachments follow the same source split as the admin screen, mirrored: this
 * page reads and writes ONLY source='customer' rows, so staff response files
 * are never shown here and cannot be removed by a revision.
 */
class EditPortalRequest extends EditRecord
{
    protected static string $resource = PortalRequestResource::class;

    /** The one status in which a customer may revise their request. */
    public const EDITABLE_STATUS = 'needs_revision';

    /** Re-submitting hands the request back to staff and re-locks editing. */
    public const STATUS_AFTER_RESUBMIT = 'under_review';

    /** Value of portal_request_attachments.source this page owns. */
    public const SOURCE_CUSTOMER = 'customer';

    /**
     * Columns the portal must never write, whatever arrives in the payload.
     * Stripped after the schema walk as a second line of defence.
     *
     * @var array<int, string>
     */
    protected const ADMIN_ONLY_COLUMNS = [
        'validation_status',
        'request_status',
        'admin_response',
        'user_id',
        'request_number',
        'request_date',
    ];

    /**
     * Paths held in the FileUpload, carried from mutateFormDataBeforeSave() to
     * afterSave().
     *
     * @var array<int, string>
     */
    protected array $customerFiles = [];

    /**
     * Gate (b). A record page is always asked with its record, on mount and on
     * every hydration; the bare call (no record) happens in listing/navigation
     * contexts, where there is nothing yet to gate — the record-bearing call
     * that follows is the one that decides.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function canAccess(array $parameters = []): bool
    {
        if (! parent::canAccess($parameters)) {
            return false;
        }

        $record = $parameters['record'] ?? null;

        if (! $record instanceof PortalRequest) {
            return true;
        }

        return $record->request_status === self::EDITABLE_STATUS;
    }

    /**
     * Load the customer's OWN files into the upload field, so they see what is
     * already attached instead of an empty box that silently drops them.
     * Filtered to source='customer' — admin files are not part of this form.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['attachments'] = $this->record
            ->attachments()
            ->where('source', self::SOURCE_CUSTOMER)
            ->orderBy('id')
            ->pluck('file_path')
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 'attachments' is a hasMany relation, not a column — leaving it in
        // $data would blow up the UPDATE. Same lift-out as on create.
        $this->customerFiles = array_values(array_filter((array) ($data['attachments'] ?? [])));
        unset($data['attachments']);

        // Belt and braces: these are not in the schema, so they should never be
        // here. Strip them anyway — this is the portal, and the cost is one
        // unset per field.
        foreach (self::ADMIN_ONLY_COLUMNS as $column) {
            unset($data[$column]);
        }

        // Re-submitting IS the hand-back: leaving needs_revision re-locks this
        // page (canAccess turns false) and returns the request to the staff
        // queue. validation_status and admin_response are deliberately not
        // touched — a revision does not un-verify or erase what staff wrote.
        $data['request_status'] = self::STATUS_AFTER_RESUBMIT;

        return $data;
    }

    /**
     * Reconcile the customer's attachment rows against what the field now
     * holds. The query is filtered to source='customer' BEFORE anything is
     * compared, so the "not in the submitted list ⇒ delete" branch can only
     * ever reach the customer's own rows, never staff's.
     */
    protected function afterSave(): void
    {
        $existing = $this->record
            ->attachments()
            ->where('source', self::SOURCE_CUSTOMER)
            ->get();

        // Removed in the UI ⇒ drop the row. The file itself is left on disk:
        // ->preserveFilenames() means two requests can legitimately point at
        // the same path, so unlinking could break another record's download.
        foreach ($existing as $attachment) {
            if (! in_array($attachment->file_path, $this->customerFiles, true)) {
                $attachment->delete();
            }
        }

        $known = $existing->pluck('file_path')->all();

        foreach ($this->customerFiles as $path) {
            if (in_array($path, $known, true)) {
                continue;
            }

            $this->record->attachments()->create([
                'title' => pathinfo($path, PATHINFO_BASENAME),
                'file_path' => $path,
                'file_type' => static::mimeTypeOf($path),
                'source' => self::SOURCE_CUSTOMER,
            ]);
        }
    }

    /**
     * Back to the read-only view, which now shows the request as under_review
     * with no edit affordance — the loop is visibly closed.
     */
    protected function getRedirectUrl(): ?string
    {
        return ViewPortalRequest::getUrl(['record' => $this->record]);
    }

    /**
     * file_type holds a MIME type. Storage can fail on an unreadable file, so
     * degrade to null rather than losing the whole save over metadata.
     */
    protected static function mimeTypeOf(string $path): ?string
    {
        try {
            return Storage::disk('local')->mimeType($path) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
