<?php

use App\Models\User;

test('guest cannot search for users', function () {
    $this->get(route('users.search', ['query' => 'john']))
        ->assertRedirect(route('login'));
});

test('authenticated user can search users by username', function () {
    $user = User::factory()->create([
        'username' => 'currentuser',
    ]);

    $john = User::factory()->create([
        'name' => 'John Doe',
        'username' => 'johndoe',
    ]);

    User::factory()->create([
        'username' => 'janedoe',
    ]);

    $this->actingAs($user)
        ->getJson(route('users.search', ['q' => 'john']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $john->id)
        ->assertJsonPath('0.username', 'johndoe');
});

test('search does not return the authenticated user', function () {
    $user = User::factory()->create([
        'username' => 'johnsmith',
    ]);

    $this->actingAs($user)
        ->getJson(route('users.search', ['query' => 'john']))
        ->assertOk()
        ->assertJsonCount(0);
});

test('empty search query returns an empty array', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('users.search'))
        ->assertOk()
        ->assertExactJson([]);
});
