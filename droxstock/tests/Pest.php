<?php

use Tests\Feature\TestHelpers;

/*
|--------------------------------------------------------------------------
| Test Case Configuration
|--------------------------------------------------------------------------
|
| This file contains the configuration for your test suite. It's loaded
| before each test and provides a clean testing environment.
|
*/

pest()->extend(\Tests\TestCase::class)
    ->in('Feature');





/*
|--------------------------------------------------------------------------
| Test Helpers
|--------------------------------------------------------------------------
|
| Register custom test helpers and utilities here.
|
*/

// Helper function to create and authenticate admin user
function createAdminUser()
{
    $user = TestHelpers::createAdminUser();
    test()->actingAs($user, 'api');
    return $user;
}

// Helper function to create and authenticate basic user
function createBasicUser()
{
    $user = TestHelpers::createBasicUser();
    test()->actingAs($user, 'api');
    return $user;
}

// Helper function to create and authenticate manager user
function createManagerUser()
{
    $user = TestHelpers::createManagerUser();
    test()->actingAs($user, 'api');
    return $user;
}

// Helper function to create unauthorized user (no roles)
function createUnauthorizedUser()
{
    return TestHelpers::createUnauthorizedUser();
}

// Helper function to generate valid user data
function validUserData(array $overrides = [])
{
    return TestHelpers::validUserData($overrides);
}

// Helper function to generate valid admin user data
function validAdminUserData(array $overrides = [])
{
    return TestHelpers::validAdminUserData($overrides);
}

// Helper function to generate valid login data
function validLoginData(array $overrides = [])
{
    return TestHelpers::validLoginData($overrides);
}

// Helper function to assert user has permissions
function assertUserHasPermissions($user, array $permissions)
{
    TestHelpers::assertUserHasPermissions($user, $permissions);
}

// Helper function to assert user does not have permissions
function assertUserDoesNotHavePermissions($user, array $permissions)
{
    TestHelpers::assertUserDoesNotHavePermissions($user, $permissions);
}

// Helper function to assert user has roles
function assertUserHasRoles($user, array $roles)
{
    TestHelpers::assertUserHasRoles($user, $roles);
}

// Helper function to assert user does not have roles
function assertUserDoesNotHaveRoles($user, array $roles)
{
    TestHelpers::assertUserDoesNotHaveRoles($user, $roles);
}
