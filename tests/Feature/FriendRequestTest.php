<?php

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;

test('guests cannot use friend request endpoints', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();
    $friendship = Friendship::factory()->between($requester, $addressee)->create();

    $this->post(route('friend-request.store', $addressee))->assertRedirect(route('login'));
    $this->patch(route('friend-request.accept', $friendship))->assertRedirect(route('login'));
    $this->delete(route('friend-requests.decline', $friendship))->assertRedirect(route('login'));
    $this->delete(route('friend-requests.cancel', $friendship))->assertRedirect(route('login'));
    $this->delete(route('friends.destroy', $friendship))->assertRedirect(route('login'));
});

test('a user can send a friend request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $this->actingAs($requester)
        ->post(route('friend-request.store', $addressee))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('friendships', [
        'requester_id' => $requester->id,
        'addressee_id' => $addressee->id,
        'status' => FriendshipStatus::PENDING->value,
    ]);
});

test('a user cannot send a friend request to themselves', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('friend-request.store', $user))
        ->assertSessionHasErrors('user');

    $this->assertDatabaseCount('friendships', 0);
});

test('the addressee can accept a friend request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();
    $friendship = Friendship::factory()->between($requester, $addressee)->pending()->create();

    $this->actingAs($addressee)
        ->patch(route('friend-request.accept', $friendship))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('friendships', [
        'id' => $friendship->id,
        'status' => FriendshipStatus::ACCEPTED->value,
    ]);
});

test('only the addressee can accept a friend request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();
    $friendship = Friendship::factory()->between($requester, $addressee)->pending()->create();

    $this->actingAs($requester)
        ->patch(route('friend-request.accept', $friendship))
        ->assertForbidden();

    $this->assertDatabaseHas('friendships', [
        'id' => $friendship->id,
        'status' => FriendshipStatus::PENDING->value,
    ]);
});

test('the addressee can decline a friend request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();
    $friendship = Friendship::factory()->between($requester, $addressee)->pending()->create();

    $this->actingAs($addressee)
        ->delete(route('friend-requests.decline', $friendship))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
});

test('only the addressee can decline a friend request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();
    $friendship = Friendship::factory()->between($requester, $addressee)->pending()->create();

    $this->actingAs($requester)
        ->delete(route('friend-requests.decline', $friendship))
        ->assertForbidden();

    $this->assertDatabaseHas('friendships', ['id' => $friendship->id]);
});

test('the requester can cancel a friend request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();
    $friendship = Friendship::factory()->between($requester, $addressee)->pending()->create();

    $this->actingAs($requester)
        ->delete(route('friend-requests.cancel', $friendship))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
});

test('only the requester can cancel a friend request', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();
    $friendship = Friendship::factory()->between($requester, $addressee)->pending()->create();

    $this->actingAs($addressee)
        ->delete(route('friend-requests.cancel', $friendship))
        ->assertForbidden();

    $this->assertDatabaseHas('friendships', ['id' => $friendship->id]);
});

test('a participant can remove an accepted friendship', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();
    $friendship = Friendship::factory()->between($requester, $addressee)->accepted()->create();

    $this->actingAs($addressee)
        ->delete(route('friends.destroy', $friendship))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
});

test('an outsider cannot remove a friendship', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();
    $outsider = User::factory()->create();
    $friendship = Friendship::factory()->between($requester, $addressee)->accepted()->create();

    $this->actingAs($outsider)
        ->delete(route('friends.destroy', $friendship))
        ->assertForbidden();

    $this->assertDatabaseHas('friendships', ['id' => $friendship->id]);
});

test('a pending friendship cannot be removed', function () {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();
    $friendship = Friendship::factory()->between($requester, $addressee)->pending()->create();

    $this->actingAs($requester)
        ->delete(route('friends.destroy', $friendship))
        ->assertSessionHasErrors('friendship');

    $this->assertDatabaseHas('friendships', ['id' => $friendship->id]);
});
