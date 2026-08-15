<?php

namespace App\Http\Controllers;

use App\Models\PortalRequestAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalRequestAttachmentController extends Controller
{
    /**
     * Stream a portal-request attachment from the private disk.
     *
     * Same shape as InquiryAttachmentController / SourcingRequestAttachmentController:
     * the file lives in storage/app/private (never public) and the route's `auth`
     * middleware is the gate.
     *
     * ONE EXTRA GUARD compared to those two: this table is reachable by external
     * portal customers, so `auth` alone would be an IDOR — any logged-in customer
     * could walk attachment ids and read another company's documents. Staff (non
     * portal users) see everything; a portal user only ever sees files hanging off
     * their own request. 404, not 403, so the id space stays unenumerable.
     */
    public function __invoke(PortalRequestAttachment $attachment): StreamedResponse
    {
        $user = auth()->user();

        abort_unless($user !== null, 404);

        if ($user->is_portal_user) {
            abort_unless(
                $attachment->portalRequest?->user_id === $user->id,
                404,
            );
        }

        abort_unless(
            $attachment->file_path && Storage::disk('local')->exists($attachment->file_path),
            404,
        );

        return Storage::disk('local')->response(
            $attachment->file_path,
            basename($attachment->file_path),
        );
    }
}
