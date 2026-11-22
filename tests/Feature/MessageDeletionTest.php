<?php

use App\Models\Message;
use App\Models\User;

it('allows the sender to delete their message', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $message = Message::factory()->create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'body' => 'Hello!',
    ]);

    $this->actingAs($sender)
        ->delete(route('messages.destroy', $message))
        ->assertRedirect();

    $message->refresh();

    expect($message->body)->toBeNull()
        ->and($message->deleted_at)->not->toBeNull();
});

it('prevents guest from deleting messages', function () {
    $message = Message::factory()->create();

    $this->delete(route('messages.destroy', $message))
        ->assertRedirect(route('login'));
});
