<?php

use App\Models\User;

test('a request updates the user last seen timestamp', function () {
    $user = User::factory()->create();

    User::query()->whereKey($user->id)->update(['last_seen_at' => now()->subMinutes(10)]);

    $this->actingAs($user)->get(route('friends.index'))->assertOk();

    expect($user->refresh()->last_seen_at->isAfter(now()->subMinutes(5)))->toBeTrue();
});
