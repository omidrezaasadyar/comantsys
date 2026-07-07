<?php

namespace App\Http\Controllers;

use App\Models\InquiryAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InquiryAttachmentController extends Controller
{
    /**
     * Stream an inquiry attachment from the private disk.
     *
     * File lives in storage/app/private (never public); access is gated by the
     * route's `auth` middleware — the browser sends the session cookie with the
     * request, so only authenticated users can fetch it.
     */
    public function __invoke(InquiryAttachment $attachment): StreamedResponse
    {
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
