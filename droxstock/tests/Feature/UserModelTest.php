<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

it('can create a user without roles', function () {
    $user = User::factory()->create([
        'name' => 'No Role User',
        'email' => 'norole@example.com'
    ]);

    expect($user->name)->toBe('No Role User');
    expect($user->email)->toBe('norole@example.com');
    expect($user->roles)->toBeEmpty();
});

it('can assign roles to user', function () {
    // Create a role first
    $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);

    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->hasRole('test_role'))->toBeTrue();
});

it('can check permissions', function () {
    // Create a permission first
    $permission = Permission::create(['name' => 'test_permission', 'guard_name' => 'api']);

    // Create a role with the permission
    $role = Role::create(['name' => 'test_role', 'guard_name' => 'api']);
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->hasPermissionTo('test_permission'))->toBeTrue();
});
