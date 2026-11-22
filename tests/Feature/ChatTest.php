<?php

use App\Models\Friendship;
use App\Models\Message;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('chat.index'))->assertRedirect(route('login'));
});

test('the chat index lists the users the current user has messaged', function () {
    $user = User::factory()->create();
    $partner = User::factory()->create();
    $stranger = User::factory()->create();

    Message::factory()->create([
        'sender_id' => $user->id,
        'recipient_id' => $partner->id,
    ]);

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('chat/index')
            ->has('conversations', 1)
            ->where('conversations.0.id', $partner->id)
            ->where('activeId', null)
            ->where('pendingFriendRequestsCount', 0)
        );
});

test('the chat show page returns the thread and marks the partner active', function () {
    $user = User::factory()->create();
    $partner = User::factory()->create();

    Friendship::factory()->between($user, $partner)->accepted()->create();

    Message::factory()->create([
        'sender_id' => $partner->id,
        'recipient_id' => $user->id,
        'body' => 'Hello there',
    ]);

    $this->actingAs($user)
        ->get(route('chat.show', $partner))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('chat/show')
            ->where('user.id', $partner->id)
            ->has('messages', 1)
            ->where('messages.0.body', 'Hello there')
            ->has('conversations', 1)
            ->where('activeId', $partner->id)
            ->where('canMessage', true)
        );
});

test('users cannot open a chat with someone they have never messaged', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($user)
        ->get(route('chat.show', $stranger))
        ->assertForbidden();
});

test('users can still read a conversation after the friendship is removed', function () {
    $user = User::factory()->create();
    $formerFriend = User::factory()->create();

    $friendship = Friendship::factory()->between($user, $formerFriend)->accepted()->create();

    Message::factory()->create([
        'sender_id' => $formerFriend->id,
        'recipient_id' => $user->id,
        'body' => 'Before we stopped being friends',
    ]);

    $friendship->delete();

    $this->actingAs($user)
        ->get(route('chat.show', $formerFriend))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('chat/show')
            ->where('user.id', $formerFriend->id)
            ->has('messages', 1)
            ->where('messages.0.body', 'Before we stopped being friends')
            ->where('canMessage', false)
        );
});

test('the pending friend request count only includes incoming requests', function () {
    $user = User::factory()->create();
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    Friendship::factory()->between($requester, $user)->pending()->create();
    Friendship::factory()->between($user, $addressee)->pending()->create();

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('pendingFriendRequestsCount', 1)
        );
});

test('the chat show page only returns messages between the two participants', function () {
    $user = User::factory()->create();
    $partner = User::factory()->create();
    $stranger = User::factory()->create();

    Friendship::factory()->between($user, $partner)->accepted()->create();

    Message::factory()->create([
        'sender_id' => $user->id,
        'recipient_id' => $partner->id,
        'body' => 'Mine',
    ]);
    Message::factory()->create([
        'sender_id' => $partner->id,
        'recipient_id' => $user->id,
        'body' => 'Theirs',
    ]);
    Message::factory()->create([
        'sender_id' => $user->id,
        'recipient_id' => $stranger->id,
        'body' => 'Not in this chat',
    ]);

    $this->actingAs($user)
        ->get(route('chat.show', $partner))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('messages', 2)
            ->where('messages.0.body', 'Mine')
            ->where('messages.1.body', 'Theirs')
        );
});

test('the chat show page returns only the latest 50 messages in order', function () {
    $user = User::factory()->create();
    $partner = User::factory()->create();

    Friendship::factory()->between($user, $partner)->accepted()->create();

    Message::factory()
        ->count(55)
        ->sequence(fn ($sequence) => ['body' => 'Message '.($sequence->index + 1)])
        ->create([
            'sender_id' => $user->id,
            'recipient_id' => $partner->id,
        ]);

    $this->actingAs($user)
        ->get(route('chat.show', $partner))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('messages', 50)
            ->where('messages.0.body', 'Message 6')
            ->where('messages.49.body', 'Message 55')
        );
});

test('conversations are ordered by their latest message', function () {
    $user = User::factory()->create();
    $older = User::factory()->create();
    $newer = User::factory()->create();

    $olderMessage = Message::factory()->create([
        'sender_id' => $user->id,
        'recipient_id' => $older->id,
    ]);
    $olderMessage->timestamps = false;
    $olderMessage->forceFill(['created_at' => now()->subDay()])->save();

    $newerMessage = Message::factory()->create([
        'sender_id' => $newer->id,
        'recipient_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('conversations.0.id', $newerMessage->sender_id)
            ->where('conversations.1.id', $olderMessage->recipient_id)
        );
});

test('users cannot open a chat with themselves', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('chat.show', $user))
        ->assertNotFound();
});

test('accepted friends without messages appear in conversations', function () {
    $user = User::factory()->create();
    $friend = User::factory()->create();
    $stranger = User::factory()->create();

    Friendship::factory()->between($user, $friend)->accepted()->create();

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('chat/index')
            ->has('conversations', 1)
            ->where('conversations.0.id', $friend->id)
            ->where('conversations.0.name', $friend->name)
            ->where('conversations.0.username', $friend->username)
        );
});

test('friends without messages appear after message conversations in alphabetical order', function () {
    $user = User::factory()->create();
    $messagePartner = User::factory()->create();
    $friendZoe = User::factory()->create(['name' => 'Zoe Friend']);
    $friendAdam = User::factory()->create(['name' => 'Adam Friend']);

    Message::factory()->create([
        'sender_id' => $user->id,
        'recipient_id' => $messagePartner->id,
    ]);

    Friendship::factory()->between($user, $friendZoe)->accepted()->create();
    Friendship::factory()->between($friendAdam, $user)->accepted()->create();

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('conversations', 3)
            ->where('conversations.0.id', $messagePartner->id)
            ->where('conversations.1.id', $friendAdam->id)
            ->where('conversations.2.id', $friendZoe->id)
        );
});

test('a friend with an existing conversation is not duplicated in conversations', function () {
    $user = User::factory()->create();
    $friend = User::factory()->create();

    Friendship::factory()->between($user, $friend)->accepted()->create();
    Message::factory()->create([
        'sender_id' => $user->id,
        'recipient_id' => $friend->id,
    ]);

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('conversations', 1)
            ->where('conversations.0.id', $friend->id)
        );
});
