<?php

use App\Models\Message;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('chat.index'))->assertRedirect(route('login'));
});

test('the chat index lists the users the current user has messaged', function () {
    $user = User::factory()->create();
    $partner = User::factory()->create();
    $stranger = User::factory()->create();

    Message::factory()->create([
        'sender_id' => $user->id,
        'recipient_id' => $partner->id,
    ]);

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('chat/index')
            ->has('conversations', 1)
            ->where('conversations.0.id', $partner->id)
            ->where('activeId', null)
        );
});

test('the chat show page returns the thread and marks the partner active', function () {
    $user = User::factory()->create();
    $partner = User::factory()->create();

    Message::factory()->create([
        'sender_id' => $partner->id,
        'recipient_id' => $user->id,
        'body' => 'Hello there',
    ]);

    $this->actingAs($user)
        ->get(route('chat.show', $partner))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('chat/show')
            ->where('user.id', $partner->id)
            ->has('messages', 1)
            ->where('messages.0.body', 'Hello there')
            ->has('conversations', 1)
            ->where('activeId', $partner->id)
        );
});

test('conversations are ordered by their latest message', function () {
    $user = User::factory()->create();
    $older = User::factory()->create();
    $newer = User::factory()->create();

    $olderMessage = Message::factory()->create([
        'sender_id' => $user->id,
        'recipient_id' => $older->id,
    ]);
    $olderMessage->timestamps = false;
    $olderMessage->forceFill(['created_at' => now()->subDay()])->save();

    $newerMessage = Message::factory()->create([
        'sender_id' => $newer->id,
        'recipient_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('conversations.0.id', $newerMessage->sender_id)
            ->where('conversations.1.id', $olderMessage->recipient_id)
        );
});

test('users cannot open a chat with themselves', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('chat.show', $user))
        ->assertNotFound();
});
