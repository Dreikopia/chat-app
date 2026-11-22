<?php

use App\Models\Friendship;
use App\Models\User;

test('a user can retrieve their friends', function () {
    $user = User::factory()->create();
    $friend = User::factory()->create();

    Friendship::factory()
        ->between($user, $friend)
        ->accepted()
        ->create();

    expect($user->friends()->get())
        ->toHaveCount(1)
        ->first()->id->toBe($friend->id);
});

test('isFriendsWith is true for accepted friendships in either direction', function () {
    $user = User::factory()->create();
    $friend = User::factory()->create();

    Friendship::factory()
        ->between($friend, $user)
        ->accepted()
        ->create();

    expect($user->isFriendsWith($friend))->toBeTrue()
        ->and($friend->isFriendsWith($user))->toBeTrue();
});

test('isFriendsWith is false for pending friendships', function () {
    $user = User::factory()->create();
    $friend = User::factory()->create();

    Friendship::factory()
        ->between($user, $friend)
        ->pending()
        ->create();

    expect($user->isFriendsWith($friend))->toBeFalse();
});
