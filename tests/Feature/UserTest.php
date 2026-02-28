<?php

use App\Models\User;

test('it can create a user', function () {
    $user = User::factory()->create();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ]);
});

test('password is hashed', function () {
    $user = User::factory()->create(['password' => 'plain-password']);

    expect($user->password)->not->toBe('plain-password');
});

test('email verified at is cast to datetime', function () {
    $user = User::factory()->create();

    expect($user->email_verified_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('unverified user has null email verified at', function () {
    $user = User::factory()->unverified()->create();

    expect($user->email_verified_at)->toBeNull();
});

test('user can access filament panel', function () {
    $user = User::factory()->create();
    $panel = \Filament\Facades\Filament::getPanel();

    expect($user->canAccessPanel($panel))->toBeTrue();
});

test('user has hidden attributes', function () {
    $user = User::factory()->create();
    $hidden = $user->getHidden();

    expect($hidden)->toContain('password')
        ->toContain('remember_token');
});

test('it can update a user', function () {
    $user = User::factory()->create();
    $user->update(['name' => 'New Name']);

    expect($user->fresh()->name)->toBe('New Name');
});

test('it can delete a user', function () {
    $user = User::factory()->create();
    $user->delete();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('email must be unique', function () {
    User::factory()->create(['email' => 'test@example.com']);
    User::factory()->create(['email' => 'test@example.com']);
})->throws(\Illuminate\Database\QueryException::class);
