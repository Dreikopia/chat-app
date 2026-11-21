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
