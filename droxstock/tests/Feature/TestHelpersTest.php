<?php

use Tests\Feature\TestHelpers;

it('can load TestHelpers class', function () {
    expect(class_exists(TestHelpers::class))->toBeTrue();
});

it('can seed roles and permissions', function () {
    TestHelpers::seedRolesAndPermissions();

    // Check if roles were created
    $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
    expect($adminRole)->not->toBeNull();

    $basicUserRole = \Spatie\Permission\Models\Role::where('name', 'basic_user')->first();
    expect($basicUserRole)->not->toBeNull();

    $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
    expect($managerRole)->not->toBeNull();
});

it('can create admin user', function () {
    $user = TestHelpers::createAdminUser();
    expect($user)->not->toBeNull();
    expect($user->hasRole('admin'))->toBeTrue();
});
