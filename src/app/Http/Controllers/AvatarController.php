<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AvatarController extends Controller
{
    /**
     * Stream a user's avatar from the private disk.
     *
     * The image lives in storage/app/private (never public); access is gated by
     * the route's `auth` middleware — the browser sends the session cookie with
     * the <img> request, so only authenticated users can fetch it.
     */
    public function __invoke(User $user): BinaryFileResponse
    {
        abort_unless(
            $user->avatar_path && Storage::disk('local')->exists($user->avatar_path),
            404,
        );

        return response()->file(Storage::disk('local')->path($user->avatar_path));
    }
}
