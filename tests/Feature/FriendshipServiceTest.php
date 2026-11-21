<?php

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use App\Services\FriendshipService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

test('a user can send a friend request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $friendship = app(FriendshipService::class)->sendRequest($requester, $addressee);

    expect($friendship->status)->toBe(FriendshipStatus::PENDING)
        ->and($friendship->accepted_at)->toBeNull()
        ->and($friendship->requester_id)->toBe($requester->id)
        ->and($friendship->addressee_id)->toBe($addressee->id);

    $this->assertDatabaseCount('friendships', 1);
});

test('a user cannot send a friend request to themselves', function () {
    $user = User::factory()->create();

    expect(fn () => app(FriendshipService::class)->sendRequest($user, $user))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('friendships', 0);
});

test('sending the same request twice returns the existing request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $first = app(FriendshipService::class)->sendRequest($requester, $addressee);
    $second = app(FriendshipService::class)->sendRequest($requester, $addressee);

    expect($second->id)->toBe($first->id)
        ->and($second->status)->toBe(FriendshipStatus::PENDING);

    $this->assertDatabaseCount('friendships', 1);
});

test('a reverse pending request is accepted automatically', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $pending = Friendship::factory()->between($addressee, $requester)->pending()->create();

    $friendship = app(FriendshipService::class)->sendRequest($requester, $addressee);

    expect($friendship->id)->toBe($pending->id)
        ->and($friendship->status)->toBe(FriendshipStatus::ACCEPTED)
        ->and($friendship->accepted_at)->not->toBeNull();

    $this->assertDatabaseCount('friendships', 1);
});

test('an existing accepted friendship is returned as is', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $accepted = Friendship::factory()->between($addressee, $requester)->accepted()->create();

    $friendship = app(FriendshipService::class)->sendRequest($requester, $addressee);

    expect($friendship->id)->toBe($accepted->id)
        ->and($friendship->status)->toBe(FriendshipStatus::ACCEPTED);

    $this->assertDatabaseCount('friendships', 1);
});

test('the addressee can accept a friend request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $friendship = Friendship::factory()->between($requester, $addressee)->pending()->create();

    $accepted = app(FriendshipService::class)->acceptRequest($friendship, $addressee);

    expect($accepted->status)->toBe(FriendshipStatus::ACCEPTED)
        ->and($accepted->accepted_at)->not->toBeNull();

    $this->assertDatabaseHas('friendships', [
        'id' => $friendship->id,
        'status' => FriendshipStatus::ACCEPTED->value,
    ]);
});

test('only the addressee can accept a friend request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $friendship = Friendship::factory()->between($requester, $addressee)->pending()->create();

    expect(fn () => app(FriendshipService::class)->acceptRequest($friendship, $requester))
        ->toThrow(AuthorizationException::class);

    $this->assertDatabaseHas('friendships', [
        'id' => $friendship->id,
        'status' => FriendshipStatus::PENDING->value,
    ]);
});

test('accepting an already accepted request is idempotent', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $friendship = Friendship::factory()->between($requester, $addressee)->accepted()->create();

    $accepted = app(FriendshipService::class)->acceptRequest($friendship, $addressee);

    expect($accepted->status)->toBe(FriendshipStatus::ACCEPTED);

    $this->assertDatabaseCount('friendships', 1);
});

test('the addressee can decline a friend request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $friendship = Friendship::factory()->between($requester, $addressee)->pending()->create();

    app(FriendshipService::class)->declineRequest($friendship, $addressee);

    $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
});

test('only the addressee can decline a friend request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $friendship = Friendship::factory()->between($requester, $addressee)->pending()->create();

    expect(fn () => app(FriendshipService::class)->declineRequest($friendship, $requester))
        ->toThrow(AuthorizationException::class);

    $this->assertDatabaseHas('friendships', ['id' => $friendship->id]);
});

test('the requester can cancel a friend request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $friendship = Friendship::factory()->between($requester, $addressee)->pending()->create();

    app(FriendshipService::class)->cancelRequest($friendship, $requester);

    $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
});

test('only the requester can cancel a friend request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $friendship = Friendship::factory()->between($requester, $addressee)->pending()->create();

    expect(fn () => app(FriendshipService::class)->cancelRequest($friendship, $addressee))
        ->toThrow(AuthorizationException::class);

    $this->assertDatabaseHas('friendships', ['id' => $friendship->id]);
});

test('the requester can remove an accepted friendship', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $friendship = Friendship::factory()->between($requester, $addressee)->accepted()->create();

    app(FriendshipService::class)->removeFriend($friendship, $requester);

    $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
});

test('the addressee can remove an accepted friendship', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $friendship = Friendship::factory()->between($requester, $addressee)->accepted()->create();

    app(FriendshipService::class)->removeFriend($friendship, $addressee);

    $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
});

test('a pending friendship cannot be removed', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $friendship = Friendship::factory()->between($requester, $addressee)->pending()->create();

    expect(fn () => app(FriendshipService::class)->removeFriend($friendship, $requester))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseHas('friendships', ['id' => $friendship->id]);
});

test('an outsider cannot remove a friendship', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();
    $outsider = User::factory()->create();

    $friendship = Friendship::factory()->between($requester, $addressee)->accepted()->create();

    expect(fn () => app(FriendshipService::class)->removeFriend($friendship, $outsider))
        ->toThrow(AuthorizationException::class);

    $this->assertDatabaseHas('friendships', ['id' => $friendship->id]);
});
