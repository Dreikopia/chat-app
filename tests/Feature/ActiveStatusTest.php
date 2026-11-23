<?php

use App\Models\Friendship;
use App\Models\Message;
use App\Models\User;

test('a user without a last seen timestamp is not active', function () {
    $user = User::factory()->create();

    expect($user->isActive())->toBeFalse();
});

test('a user seen within the active window is active', function () {
    $user = User::factory()->create()
        ->forceFill(['last_seen_at' => now()->subMinutes(User::ACTIVE_WINDOW_MINUTES - 1)]);

    expect($user->isActive())->toBeTrue();
});

test('a user seen beyond the active window is not active', function () {
    $user = User::factory()->create()
        ->forceFill(['last_seen_at' => now()->subMinutes(User::ACTIVE_WINDOW_MINUTES + 1)]);

    expect($user->isActive())->toBeFalse();
});

test('the friends page marks recently active friends as active', function () {
    $user = User::factory()->create();
    $friend = User::factory()->create();

    Friendship::factory()
        ->between($user, $friend)
        ->accepted()
        ->create();

    User::query()->whereKey($friend->id)->update(['last_seen_at' => now()->subMinutes(1)]);

    $this->actingAs($user)
        ->get(route('friends.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('friends/index')
            ->where('friends.0.is_active', true)
        );
});

test('the friends page marks friends without a recent last seen timestamp as inactive', function () {
    $user = User::factory()->create();
    $friend = User::factory()->create();

    Friendship::factory()
        ->between($user, $friend)
        ->accepted()
        ->create();

    $this->actingAs($user)
        ->get(route('friends.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('friends/index')
            ->where('friends.0.is_active', false)
        );
});

test('the chat show page marks the open conversation partner as active when seen recently', function () {
    $user = User::factory()->create();
    $partner = User::factory()->create();

    Friendship::factory()->between($user, $partner)->accepted()->create();

    User::query()->whereKey($partner->id)->update(['last_seen_at' => now()->subMinutes(1)]);

    $this->actingAs($user)
        ->get(route('chat.show', $partner))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('chat/show')
            ->where('user.is_active', true)
        );
});

test('conversations carry the partner active status to the chat sidebar', function () {
    $user = User::factory()->create();
    $partner = User::factory()->create();

    Friendship::factory()->between($user, $partner)->accepted()->create();

    Message::factory()->create([
        'sender_id' => $user->id,
        'recipient_id' => $partner->id,
    ]);

    User::query()->whereKey($partner->id)->update(['last_seen_at' => now()->subMinutes(1)]);

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('chat/index')
            ->where('conversations.0.is_active', true)
        );
});
