<?php

it('can access the application', function () {
    $response = $this->get('/');

    expect($response->status())->toBe(200);
});

it('can make a JSON request', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    // This should return 422 (Unprocessable Entity) for missing data
    expect($response->status())->toBe(422);
});
