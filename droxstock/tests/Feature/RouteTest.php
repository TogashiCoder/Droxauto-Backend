<?php

it('can access the registration route', function () {
    $response = $this->get('/api/v1/auth/register');

    // This should either return 405 (Method Not Allowed) for GET request
    // or 422 (Unprocessable Entity) for missing data
    $status = $response->status();
    dump("Registration route GET response status: " . $status);
    expect($status === 405 || $status === 422)->toBeTrue();
});

it('can access the login route', function () {
    $response = $this->get('/api/v1/auth/login');

    // This should either return 405 (Method Not Allowed) for GET request
    // or 422 (Unprocessable Entity) for missing data
    $status = $response->status();
    expect($status === 405 || $status === 422)->toBeTrue();
});

it('can make POST request to registration route', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    // This should return 422 (Unprocessable Entity) for missing data
    $status = $response->status();
    dump("Registration route POST response status: " . $status);
    expect($status)->toBe(422);
});

it('can access the home route', function () {
    $response = $this->get('/');

    expect($response->status())->toBe(200);
});
