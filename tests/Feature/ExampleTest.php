<?php

use App\Models\User;

test('guests are redirected from home to the login page', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('authenticated users are redirected from home to their chats', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('home'))->assertRedirect(route('chat.index'));
});
