<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Models\User;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request, User $user)
    {
        $sender = $request->user();

        abort_if($sender->is($user), 404); // prevent sending to self

        abort_unless($sender->isFriendsWith($user), 403); // must be friends

        Message::create([
            'sender_id' => $sender->id,
            'recipient_id' => $user->id,
            'body' => $request->validated('body'),
        ]);

        return to_route('chat.show', $user);
    }
}
