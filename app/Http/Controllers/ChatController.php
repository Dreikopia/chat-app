<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class ChatController extends Controller
{
    // find everyone the currently logged-in user either sender or receiver
    public function index(Request $request)
    {
        return Inertia::render('chat/index', [
            'conversations' => $this->conversationsFor($request->user()),
            'activeId' => null,
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

        return Inertia::render('chat/show', [
            'user' => $user,
            'messages' => $messages,
            'conversations' => $this->conversationsFor($currentUser),
            'activeId' => $user->id,
        ]);
    }

    /**
     * The distinct users the given user has exchanged messages with, newest first.
     *
     * @return Collection<int, User>
     */
    private function conversationsFor(User $user): Collection
    {
        return Message::query()
            ->with([
                'sender:id,name,username,avatar_path',
                'recipient:id,name,username,avatar_path',
            ])
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Message $message) => $message->sender_id === $user->id
                ? $message->recipient
                : $message->sender)
            ->unique('id')
            ->values();
    }
}
