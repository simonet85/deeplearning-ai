<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserPhotoController extends Controller
{
    /**
     * Profile photos live on the private disk and are served only to signed-in users: everyone can see their
     * own photo, anyone who can see the staff dashboard can see every photo, and an agent can never see another's.
     */
    public function __invoke(Request $request, User $user): StreamedResponse
    {
        abort_unless($request->user()->is($user) || $request->user()->can('dashboard.view'), 403);
        $disk = Storage::disk(User::PHOTO_DISK);

        abort_unless($user->profile_photo_path && $disk->exists($user->profile_photo_path), 404);

        return $disk->response($user->profile_photo_path, null, ['Cache-Control' => 'private, max-age=86400']);
    }
}
