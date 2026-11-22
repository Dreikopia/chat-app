<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatController extends Controller
{
    // find everyone the currently logged-in user either sender or receiver
    public function index(Request $request)
    {
        return Inertia::render('chat/index', [
            'conversations' => $request->user()->conversations(),
            'activeId' => null,
        ]);
    }

    // retrieve only messages exchanged between user1 and user2, regardless of who sent them.
    public function show(Request $request, User $user)
    {
        $currentUser = $request->user();
        abort_if($currentUser->is($user), 404);
        abort_unless($currentUser->isFriendsWith($user), 403);

        $messages = Message::query()
            ->where(function ($query) use ($currentUser, $user) {
                $query->where(function ($query) use ($currentUser, $user) {
                    $query->where('sender_id', $currentUser->id)
                        ->where('recipient_id', $user->id);
                })
                    ->orWhere(function ($query) use ($currentUser, $user) {
                        $query->where('sender_id', $user->id)
                            ->where('recipient_id', $currentUser->id);
                    });
            })
            ->latest('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return Inertia::render('chat/show', [
            'user' => $user,
            'messages' => $messages,
            'conversations' => $currentUser->conversations(),
            'activeId' => $user->id,
        ]);
    }
}
