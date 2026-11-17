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
        $currentUser = $request->user();

        $messages = Message::query()
            ->with([
                'sender:id,name,username,avatar_path',
                'recipient:id,name,username,avatar_path',
            ])
            ->where(function ($query) use ($currentUser) {
                $query->where('sender_id', $currentUser->id)
                    ->orWhere('recipient_id', $currentUser->id);
            })
            ->latest()
            ->get();

        $conversations = $messages
            ->map(function (Message $message) use ($currentUser) {
                return $message->sender_id === $currentUser->id
                ? $message->recipient
                : $message->sender;
            })
            ->unique('id')
            ->values();

        return Inertia::render('Chat/Index', [
            'messages' => $messages,
            'conversations' => $conversations,
        ]);
    }

    // retrieve only messages exchanged between user1 and user2, regardless of who sent them.
    public function show(Request $request, User $user)
    {
        $currentUser = $request->user();
        abort_if($currentUser->is($user), 404);

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
            ->orderBy('created_at')
            ->get();

        return Inertia::render('Chat/Show', [
            'user' => $user,
            'messages' => $messages,
        ]);
    }
}
