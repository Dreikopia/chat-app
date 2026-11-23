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

        $isFriend = $currentUser->isFriendsWith($user);

        abort_unless($isFriend || $currentUser->hasConversationWith($user), 403);

        $user->setAttribute('is_active', $user->isActive());

        // Opening the thread reads every incoming message from this partner.
        Message::query()
            ->where('sender_id', $user->id)
            ->where('recipient_id', $currentUser->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

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

        $messages = $messages->map(fn (Message $message) => [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'recipient_id' => $message->recipient_id,
            // Deleting hides the message from the recipient; the sender keeps it.
            'body' => $message->deleted_at && $message->sender_id !== $currentUser->id
                ? null
                : $message->body,
            'is_deleted' => $message->deleted_at !== null,
            'read_at' => $message->read_at?->toISOString(),
            'created_at' => $message->created_at->toISOString(),
        ]);

        return Inertia::render('chat/show', [
            'user' => $user,
            'messages' => $messages,
            'conversations' => $currentUser->conversations(),
            'activeId' => $user->id,
            'canMessage' => $isFriend,
        ]);
    }
}
