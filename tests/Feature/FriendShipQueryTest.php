<?php

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

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

test('the friends page includes the friend avatar', function () {
    $user = User::factory()->create();
    $friend = User::factory()->create(['avatar_path' => 'avatars/friend.jpg']);

    Friendship::factory()
        ->between($user, $friend)
        ->accepted()
        ->create();

    $this->actingAs($user)
        ->get(route('friends.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('friends/index')
            ->where('friends.0.avatar', Storage::disk('public')->url('avatars/friend.jpg'))
        );
});

test('the friends page component file matches the case of the component name', function () {
    expect(scandir(resource_path('js/pages/friends')))
        ->toContain('index.tsx');
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
