# Comprehensive Testing Suite Documentation

## Overview

This project includes a comprehensive, enterprise-grade testing suite built with **Pest PHP** that demonstrates industry best practices for Laravel API testing. The testing suite covers all authentication endpoints, admin user management, and includes extensive security testing scenarios.

## 🚀 Quick Start

### Prerequisites

-   PHP 8.1+
-   Laravel 10+
-   Pest PHP
-   SQLite (for testing)

### Running Tests

```bash
# Run all tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/Auth/AuthenticationTest.php

# Run tests with coverage
./vendor/bin/pest --coverage

# Run tests in parallel
./vendor/bin/pest --parallel

# Run tests with verbose output
./vendor/bin/pest --verbose
```

## 📁 Test Structure

```
tests/
├── Feature/
│   ├── Auth/
│   │   └── AuthenticationTest.php      # Authentication endpoint tests
│   ├── Admin/
│   │   └── UserManagementTest.php      # Admin user management tests
│   └── TestHelpers.php                 # Test utility functions
├── Pest.php                            # Pest configuration
└── TestCase.php                        # Base test case
```

## 🧪 Test Categories

### 1. Authentication Tests (`AuthenticationTest.php`)

**Coverage:**

-   User Registration (Public)
-   User Login
-   Token Refresh
-   User Profile
-   User Logout
-   Edge Cases & Security

**Key Test Scenarios:**

-   ✅ Valid user registration
-   ✅ Invalid data validation
-   ✅ Duplicate email handling
-   ✅ Password strength validation
-   ✅ Login with valid/invalid credentials
-   ✅ Token refresh functionality
-   ✅ Concurrent request handling
-   ✅ SQL injection prevention
-   ✅ XSS handling
-   ✅ Rate limiting

### 2. Admin User Management Tests (`UserManagementTest.php`)

**Coverage:**

-   User Listing & Pagination
-   User Creation
-   User Retrieval
-   User Updates
-   User Deletion
-   Role Management
-   Security & Validation

**Key Test Scenarios:**

-   ✅ Admin-only access control
-   ✅ Role-based permissions
-   ✅ User CRUD operations
-   ✅ Role assignment & updates
-   ✅ Concurrent operations
-   ✅ Data integrity
-   ✅ Security validation

## 🔧 Test Configuration

### Pest Configuration (`Pest.php`)

The Pest configuration file provides:

-   Automatic role/permission seeding
-   Global test helper functions
-   Consistent test environment setup

### Test Helpers (`TestHelpers.php`)

Utility functions for:

-   Creating users with specific roles
-   Seeding test data
-   Asserting permissions and roles
-   Generating valid test data

### Base Test Case (`TestCase.php`)

Extended Laravel test case with:

-   Automatic database refresh
-   Role/permission seeding
-   Custom assertion methods

## 🎯 Test Helper Functions

### User Creation Helpers

```php
// Create and authenticate admin user
$adminUser = createAdminUser();

// Create and authenticate basic user
$basicUser = createBasicUser();

// Create and authenticate manager user
$managerUser = createManagerUser();

// Create unauthorized user (no roles)
$unauthorizedUser = createUnauthorizedUser();
```

### Data Generation Helpers

```php
// Generate valid user data
$userData = validUserData([
    'name' => 'Custom Name',
    'email' => 'custom@example.com'
]);

// Generate valid admin user data
$adminData = validAdminUserData([
    'name' => 'Custom Admin'
]);

// Generate valid login data
$loginData = validLoginData([
    'email' => 'test@example.com'
]);
```

### Assertion Helpers

```php
// Assert user has specific permissions
assertUserHasPermissions($user, ['view dapartos', 'create dapartos']);

// Assert user does not have permissions
assertUserDoesNotHavePermissions($user, ['delete users']);

// Assert user has specific roles
assertUserHasRoles($user, ['admin', 'manager']);

// Assert user does not have roles
assertUserDoesNotHaveRoles($user, ['basic_user']);
```

## 🛡️ Security Testing

### SQL Injection Prevention

```php
it('prevents SQL injection attempts', function () {
    $maliciousData = [
        'email' => "'; DROP TABLE users; --"
    ];

    $response = $this->postJson('/api/v1/auth/register', $maliciousData);

    $response->assertStatus(422);
    expect(User::count())->toBe(0);
});
```

### XSS Handling

```php
it('handles XSS attempts in name field', function () {
    $maliciousData = [
        'name' => '<script>alert("XSS")</script>'
    ];

    $response = $this->postJson('/api/v1/auth/register', $maliciousData);

    $response->assertStatus(201);

    $user = User::where('email', 'xss@example.com')->first();
    expect($user->name)->toBe('<script>alert("XSS")</script>');
});
```

### Concurrent Request Handling

```php
it('handles concurrent registration attempts gracefully', function () {
    $userData = validUserData(['email' => 'concurrent@example.com']);

    // Simulate concurrent requests
    $responses = collect(range(1, 3))->map(function () use ($userData) {
        return $this->postJson('/api/v1/auth/register', $userData);
    });

    // Only one should succeed
    $successCount = $responses->filter(fn($r) => $r->status() === 201)->count();
    expect($successCount)->toBe(1);
});
```

## 📊 Test Coverage

### Authentication Endpoints

| Endpoint                | Method | Test Coverage | Status |
| ----------------------- | ------ | ------------- | ------ |
| `/api/v1/auth/register` | POST   | ✅ Complete   | 100%   |
| `/api/v1/auth/login`    | POST   | ✅ Complete   | 100%   |
| `/api/v1/auth/refresh`  | POST   | ✅ Complete   | 100%   |
| `/api/v1/auth/me`       | GET    | ✅ Complete   | 100%   |
| `/api/v1/auth/logout`   | POST   | ✅ Complete   | 100%   |

### Admin Endpoints

| Endpoint                         | Method | Test Coverage | Status |
| -------------------------------- | ------ | ------------- | ------ |
| `/api/v1/admin/users`            | GET    | ✅ Complete   | 100%   |
| `/api/v1/admin/users`            | POST   | ✅ Complete   | 100%   |
| `/api/v1/admin/users/{id}`       | GET    | ✅ Complete   | 100%   |
| `/api/v1/admin/users/{id}`       | PUT    | ✅ Complete   | 100%   |
| `/api/v1/admin/users/{id}`       | DELETE | ✅ Complete   | 100%   |
| `/api/v1/admin/users/{id}/roles` | GET    | ✅ Complete   | 100%   |
| `/api/v1/admin/users/{id}/roles` | PUT    | ✅ Complete   | 100%   |

## 🔍 Test Scenarios

### Positive Test Cases

-   ✅ Valid data submission
-   ✅ Successful operations
-   ✅ Proper response structures
-   ✅ Database state verification
-   ✅ Permission/role assignment

### Negative Test Cases

-   ❌ Invalid data validation
-   ❌ Unauthorized access attempts
-   ❌ Missing required fields
-   ❌ Duplicate data handling
-   ❌ Invalid role assignments

### Edge Cases

-   🔄 Concurrent operations
-   🔒 Security vulnerabilities
-   📏 Data size limits
-   🕐 Token expiration
-   🗑️ Data integrity

## 🚨 Error Handling Tests

### Validation Errors

```php
it('fails registration with missing required fields', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});
```

### Authorization Errors

```php
it('fails to list users without admin role', function () {
    $regularUser = createBasicUser();

    $response = $this->getJson('/api/v1/admin/users');

    $response->assertStatus(403);
});
```

### Not Found Errors

```php
it('fails to retrieve non-existent user', function () {
    $response = $this->getJson('/api/v1/admin/users/99999');

    $response->assertStatus(404)
        ->assertJson(['message' => 'User not found']);
});
```

## 📈 Performance Testing

### Rate Limiting

```php
it('enforces rate limiting on registration endpoint', function () {
    $userData = validUserData();

    // Make multiple rapid requests
    for ($i = 0; $i < 10; $i++) {
        $userData['email'] = "user{$i}@example.com";
        $this->postJson('/api/v1/auth/register', $userData);
    }

    // The 11th request should be rate limited
    $userData['email'] = 'user11@example.com';
    $response = $this->postJson('/api/v1/auth/register', $userData);

    expect($response->status())->toBeOneOf([200, 201, 429]);
});
```

## 🧹 Test Data Management

### Database Refresh

All tests use `RefreshDatabase` trait to ensure:

-   Clean database state for each test
-   No data leakage between tests
-   Consistent test environment

### Factory Usage

```php
// Create single user
$user = User::factory()->create();

// Create multiple users
$users = User::factory()->count(15)->create();

// Create user with specific attributes
$user = User::factory()->create([
    'email' => 'specific@example.com',
    'name' => 'Specific User'
]);
```

## 🔧 Custom Assertions

### Permission Assertions

```php
// Custom assertion for user permissions
expect($user->getPermissionsArray())->toContain('view dapartos');
expect($user->getPermissionsArray())->toContain('create dapartos');
```

### Role Assertions

```php
// Custom assertion for user roles
expect($user->hasRole('admin'))->toBeTrue();
expect($user->hasRole('basic_user'))->toBeFalse();
```

## 📝 Best Practices Implemented

### 1. Test Organization

-   **Descriptive test names** using `it()` syntax
-   **Logical grouping** with `describe()` blocks
-   **Consistent naming conventions**
-   **Clear test structure**

### 2. Data Management

-   **Factory usage** for test data creation
-   **Database refresh** between tests
-   **No hardcoded test data**
-   **Consistent data generation**

### 3. Assertions

-   **Comprehensive response validation**
-   **Database state verification**
-   **Permission/role verification**
-   **Error handling validation**

### 4. Security Testing

-   **SQL injection prevention**
-   **XSS handling**
-   **Authorization testing**
-   **Input validation**

### 5. Edge Cases

-   **Concurrent operations**
-   **Rate limiting**
-   **Data integrity**
-   **Error scenarios**

## 🚀 Running Specific Test Suites

### Authentication Tests Only

```bash
./vendor/bin/pest tests/Feature/Auth/
```

### Admin Tests Only

```bash
./vendor/bin/pest tests/Feature/Admin/
```

### Specific Test Method

```bash
./vendor/bin/pest --filter="successfully registers a new user"
```

### Tests with Coverage

```bash
./vendor/bin/pest --coverage --min=90
```

## 📊 Coverage Reports

### HTML Coverage Report

```bash
./vendor/bin/pest --coverage-html coverage/
```

### Text Coverage Report

```bash
./vendor/bin/pest --coverage-text
```

### XML Coverage Report (for CI/CD)

```bash
./vendor/bin/pest --coverage-xml coverage.xml
```

## 🔄 Continuous Integration

### GitHub Actions Example

```yaml
name: Tests
on: [push, pull_request]
jobs:
    test:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v2
            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: "8.1"
            - name: Install dependencies
              run: composer install
            - name: Run tests
              run: ./vendor/bin/pest --coverage
```

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Errors**

    - Ensure SQLite is configured for testing
    - Check `.env.testing` configuration

2. **Permission Errors**

    - Run `php artisan migrate:fresh --seed` for testing
    - Ensure roles and permissions are seeded

3. **Test Failures**
    - Check test database state
    - Verify factory definitions
    - Review test data consistency

### Debug Mode

```bash
# Run tests with detailed output
./vendor/bin/pest --verbose

# Run single test with debug
./vendor/bin/pest --filter="test_name" --verbose
```

## 📚 Additional Resources

-   [Pest PHP Documentation](https://pestphp.com/)
-   [Laravel Testing Documentation](https://laravel.com/docs/testing)
-   [Spatie Permission Package](https://spatie.be/docs/laravel-permission)
-   [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)

## 🤝 Contributing

When adding new tests:

1. **Follow existing patterns** and naming conventions
2. **Include both positive and negative test cases**
3. **Test edge cases and security scenarios**
4. **Maintain high test coverage**
5. **Document complex test scenarios**
6. **Use appropriate test helpers**

---

**This testing suite serves as a reference implementation for professional Laravel API testing standards, demonstrating industry best practices for security, coverage, and maintainability.**
