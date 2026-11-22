<?php

use App\Events\MessageSent;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('friends can send a message', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    Friendship::factory()->between($sender, $recipient)->accepted()->create();

    Event::fake([MessageSent::class]);

    $this->actingAs($sender)
        ->post(route('messages.store', $recipient), [
            'body' => "Hey what's up twin",
        ])
        ->assertRedirect(route('chat.show', $recipient));

    Event::assertDispatched(
        MessageSent::class,
        fn (MessageSent $event) => $event->message->recipient_id === $recipient->id,
    );

    $this->assertDatabaseHas('messages', [
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'body' => "Hey what's up twin",
    ]);
});

test('users cannot message someone who is not a friend', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $this->actingAs($sender)
        ->post(route('messages.store', $recipient), ['body' => 'Hello'])
        ->assertForbidden();

    $this->assertDatabaseCount('messages', 0);
});

test('a pending friendship is not enough to send a message', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    Friendship::factory()->between($sender, $recipient)->pending()->create();

    $this->actingAs($sender)
        ->post(route('messages.store', $recipient), ['body' => 'Hello'])
        ->assertForbidden();

    $this->assertDatabaseCount('messages', 0);
});

test('users cannot message themselves', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('messages.store', $user), ['body' => 'Hello'])
        ->assertNotFound();

    $this->assertDatabaseCount('messages', 0);
});

test('the message body is limited to 1000 characters', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    Friendship::factory()->between($sender, $recipient)->accepted()->create();

    $this->actingAs($sender)
        ->post(route('messages.store', $recipient), ['body' => str_repeat('a', 1000)])
        ->assertRedirect(route('chat.show', $recipient));

    $this->actingAs($sender)
        ->post(route('messages.store', $recipient), ['body' => str_repeat('a', 1001)])
        ->assertSessionHasErrors('body');

    $this->assertDatabaseCount('messages', 1);
});

test('the sender is always the authenticated user', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $other = User::factory()->create();

    Friendship::factory()->between($sender, $recipient)->accepted()->create();

    $this->actingAs($sender)
        ->post(route('messages.store', $recipient), [
            'sender_id' => $other->id,
            'recipient_id' => $other->id,
            'body' => 'Hello',
        ])
        ->assertRedirect(route('chat.show', $recipient));

    $this->assertDatabaseHas('messages', [
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
    ]);
});
