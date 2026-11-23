<?php

namespace App\Http\Controllers;

use App\Events\MessageDeleted;
use App\Events\MessageSent;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Models\User;
use Throwable;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request, User $user)
    {
        $sender = $request->user();

        abort_if($sender->is($user), 404); // prevent sending to self

        abort_unless($sender->isFriendsWith($user), 403); // must be friends

        $message = Message::create([
            'sender_id' => $sender->id,
            'recipient_id' => $user->id,
            'body' => $request->validated('body'),
        ]);

        try {
            MessageSent::dispatch($message);
        } catch (Throwable $e) {
            report($e); // a broadcast failure must not fail the saved message
        }

        return to_route('chat.show', $user);
    }

    public function destroy(Message $message)
    {
        $this->authorize('delete', $message); // checks the policy

        if ($message->deleted_at !== null) {
            return back();
        }

        // Keeps the body so the sender still sees their own message.
        $message->update(['deleted_at' => now()]);

        try {
            MessageDeleted::dispatch($message);
        } catch (Throwable $e) {
            report($e); // a broadcast failure must not fail the deletion
        }

        return back();
    }
}
