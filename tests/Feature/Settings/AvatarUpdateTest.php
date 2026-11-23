<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests cannot upload an avatar', function () {
    $response = $this->post(route('profile.avatar'), [
        'avatar' => UploadedFile::fake()->image('photo.jpg'),
    ]);

    $response->assertRedirect(route('login'));
});

test('avatar can be uploaded', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->post(route('profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('photo.jpg'),
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->avatar_path)->toStartWith('avatars/');
    Storage::disk('public')->assertExists($user->avatar_path);
});

test('avatar attribute exposes the public url', function () {
    $user = User::factory()->create(['avatar_path' => 'avatars/photo.jpg']);

    expect($user->avatar)->toBe(Storage::disk('public')->url('avatars/photo.jpg'));
});

test('user without an avatar has no avatar url', function () {
    $user = User::factory()->create(['avatar_path' => null]);

    expect($user->avatar)->toBeNull();
});

test('uploading a new avatar deletes the previous file', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/old.jpg', 'old');

    $user = User::factory()->create(['avatar_path' => 'avatars/old.jpg']);

    $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->post(route('profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('new.jpg'),
        ])
        ->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing('avatars/old.jpg');
    Storage::disk('public')->assertExists($user->refresh()->avatar_path);
});

test('avatar is required', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->post(route('profile.avatar'), []);

    $response->assertSessionHasErrors([
        'avatar' => 'Choose a JPG, PNG, or WebP image up to 2 MB.',
    ]);
});

test('non image files are rejected', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/old.jpg', 'old');

    $user = User::factory()->create(['avatar_path' => 'avatars/old.jpg']);

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->post(route('profile.avatar'), [
            'avatar' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ]);

    $response->assertSessionHasErrors([
        'avatar' => 'Choose a JPG, PNG, or WebP image up to 2 MB.',
    ]);

    expect($user->refresh()->avatar_path)->toBe('avatars/old.jpg');
    Storage::disk('public')->assertExists('avatars/old.jpg');
});

test('images larger than two megabytes are rejected', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/old.jpg', 'old');

    $user = User::factory()->create(['avatar_path' => 'avatars/old.jpg']);

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->post(route('profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('big.jpg')->size(3000),
        ]);

    $response->assertSessionHasErrors([
        'avatar' => 'Choose a JPG, PNG, or WebP image up to 2 MB.',
    ]);

    expect($user->refresh()->avatar_path)->toBe('avatars/old.jpg');
    Storage::disk('public')->assertExists('avatars/old.jpg');
});

test('avatar uploads are limited to three per hour', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    foreach (range(1, 3) as $attempt) {
        $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->post(route('profile.avatar'), [
                'avatar' => UploadedFile::fake()->image("photo-{$attempt}.jpg"),
            ])
            ->assertSessionHasNoErrors();
    }

    $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->post(route('profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('photo-4.jpg'),
        ])
        ->assertStatus(429)
        ->assertHeader('Retry-After');
});
