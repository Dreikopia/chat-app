<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AvatarUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AvatarController extends Controller
{
    /**
     * Update the user's profile picture.
     */
    public function store(AvatarUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        /** @var UploadedFile $avatar */
        $avatar = $request->file('avatar');

        $path = $avatar->store('avatars', 'public');

        if ($user->avatar_path !== null) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update(['avatar_path' => $path]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photo updated.')]);

        return back();
    }
}
