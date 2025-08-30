<?php

use App\Models\User;

it('can create a user in a fresh database', function () {
    $user = User::factory()->create([
        'name' => 'Fresh User',
        'email' => 'fresh@example.com'
    ]);

    expect($user->name)->toBe('Fresh User');
    expect($user->email)->toBe('fresh@example.com');
});

it('can create another user in a fresh database', function () {
    $user = User::factory()->create([
        'name' => 'Another User',
        'email' => 'another@example.com'
    ]);

    expect($user->name)->toBe('Another User');
    expect($user->email)->toBe('another@example.com');
});
