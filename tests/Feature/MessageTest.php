<?php

use App\Models\User;

test('authenticated user can send a message', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $this->actingAs($sender)
        ->post(route('messages.store', $recipient), [
            'body' => "Hey what's up twin",
        ])
        ->assertRedirect(route('chat.show', $recipient));

    $this->assertDatabaseHas('messages', [
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'body' => "Hey what's up twin",
    ]);
});
