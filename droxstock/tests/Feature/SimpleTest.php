<?php

use App\Models\User;

it('can create a user model', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com'
    ]);

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
});

it('can access the application', function () {
    $response = $this->get('/');

    expect($response->status())->toBe(200);
});
