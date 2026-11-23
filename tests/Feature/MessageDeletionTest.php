<?php

use App\Models\Message;
use App\Models\User;

it('lets the sender delete a message for the recipient', function () {
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

    expect($message->body)->toBe('Hello!')
        ->and($message->deleted_at)->not->toBeNull();
});

it('hides a deleted message from the recipient but not the sender', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    Message::factory()->create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'body' => 'Hello!',
        'deleted_at' => now(),
    ]);

    $this->actingAs($sender)
        ->get(route('chat.show', $recipient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('messages.0.body', 'Hello!')
            ->where('messages.0.is_deleted', true)
        );

    $this->actingAs($recipient)
        ->get(route('chat.show', $sender))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('messages.0.body', null)
            ->where('messages.0.is_deleted', true)
        );
});

it('prevents the recipient from deleting a message', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $message = Message::factory()->create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'body' => 'Hello!',
    ]);

    $this->actingAs($recipient)
        ->delete(route('messages.destroy', $message))
        ->assertForbidden();

    expect($message->refresh()->deleted_at)->toBeNull();
});

it('prevents guest from deleting messages', function () {
    $message = Message::factory()->create();

    $this->delete(route('messages.destroy', $message))
        ->assertRedirect(route('login'));
});
