# RBAC (Role-Based Access Control) System Documentation

## Overview

This document describes the complete RBAC system implementation for the Laravel application. The system provides comprehensive role and permission management with enterprise-grade security and performance.

## System Architecture

The RBAC system consists of the following core components:

1. **Role Management** - CRUD operations for roles
2. **Permission Management** - CRUD operations for permissions  
3. **Role Permission Management** - Assigning/removing permissions to/from roles
4. **User Role Assignment** - Assigning/removing roles to/from users
5. **User Permission Management** - Direct permission assignment to users

## Core Components

### 1. Models

- **User** - Extends Laravel's User model with Spatie Permission traits
- **Role** - Spatie Permission Role model
- **Permission** - Spatie Permission Permission model

### 2. Controllers

- `RoleController` - Handles role CRUD operations
- `PermissionController` - Handles permission CRUD operations
- `RolePermissionController` - Manages role-permission relationships
- `UserRoleController` - Manages user-role assignments
- `UserPermissionController` - Manages direct user permissions

### 3. Services

- `RoleService` - Business logic for role operations
- `PermissionService` - Business logic for permission operations

### 4. Resources

- `RoleResource` - API response transformation for roles
- `PermissionResource` - API response transformation for permissions

### 5. Form Requests

- `CreateRoleRequest` - Validation for role creation
- `UpdateRoleRequest` - Validation for role updates
- `CreatePermissionRequest` - Validation for permission creation

## API Endpoints

### Authentication

All RBAC endpoints require authentication via Laravel Sanctum. Include the Bearer token in the Authorization header:

```http
Authorization: Bearer {token}
```

### Base URL

```
/api/v1/admin
```

### 1. Role Management

#### Create Role
```http
POST /api/v1/admin/roles
```

**Request Body:**
```json
{
    "name": "editor",
    "guard_name": "api",
    "description": "Content editor role",
    "permissions": ["view_posts", "edit_posts"]
}
```

**Response:**
```json
{
    "success": true,
    "message": "Role created successfully",
    "data": {
        "role": {
            "id": 1,
            "name": "editor",
            "guard_name": "api",
            "description": "Content editor role",
            "permissions_count": 2,
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z",
            "is_system_role": false,
            "can_be_deleted": true,
            "can_be_modified": true,
            "links": {
                "self": "/api/v1/admin/roles/1",
                "edit": "/api/v1/admin/roles/1",
                "delete": "/api/v1/admin/roles/1",
                "permissions": "/api/v1/admin/roles/1/permissions"
            }
        }
    }
}
```

#### Get All Roles
```http
GET /api/v1/admin/roles
```

**Query Parameters:**
- `page` - Page number for pagination
- `per_page` - Items per page (default: 15)
- `search` - Search term for role names
- `sort_by` - Sort field (name, created_at, updated_at)
- `sort_order` - Sort direction (asc, desc)

#### Get Role by ID
```http
GET /api/v1/admin/roles/{id}
```

#### Update Role
```http
PUT /api/v1/admin/roles/{id}
```

#### Delete Role
```http
DELETE /api/v1/admin/roles/{id}
```

### 2. Permission Management

#### Create Permission
```http
POST /api/v1/admin/permissions
```

**Request Body:**
```json
{
    "name": "edit_posts",
    "guard_name": "api",
    "description": "Permission to edit blog posts"
}
```

#### Get All Permissions
```http
GET /api/v1/admin/permissions
```

#### Get Permission by ID
```http
GET /api/v1/admin/permissions/{id}
```

#### Update Permission
```http
PUT /api/v1/admin/permissions/{id}
```

#### Delete Permission
```http
DELETE /api/v1/admin/permissions/{id}
```

#### Get Permission Statistics
```http
GET /api/v1/admin/permissions/statistics
```

#### Clone Permission
```http
POST /api/v1/admin/permissions/{id}/clone
```

### 3. Role Permission Management

#### Assign Permission to Role
```http
POST /api/v1/admin/roles/assign-permission
```

**Request Body:**
```json
{
    "role_id": 1,
    "permission_id": 5
}
```

#### Assign Multiple Permissions to Role
```http
POST /api/v1/admin/roles/assign-multiple-permissions
```

**Request Body:**
```json
{
    "role_id": 1,
    "permission_ids": [5, 6, 7]
}
```

#### Remove Permission from Role
```http
POST /api/v1/admin/roles/remove-permission
```

#### Remove All Permissions from Role
```http
POST /api/v1/admin/roles/remove-all-permissions
```

### 4. User Role Assignment

#### Assign Role to User
```http
POST /api/v1/admin/users/assign-role
```

**Request Body:**
```json
{
    "user_id": 10,
    "role_id": 3
}
```

#### Assign Multiple Roles to User
```http
POST /api/v1/admin/users/assign-multiple-roles
```

**Request Body:**
```json
{
    "user_id": 10,
    "role_ids": [3, 4, 5]
}
```

#### Remove Role from User
```http
POST /api/v1/admin/users/remove-role
```

#### Remove All Roles from User
```http
POST /api/v1/admin/users/remove-all-roles
```

#### Get User Permissions
```http
GET /api/v1/admin/users/{id}/permissions
```

### 5. User Permission Management

#### Assign Permission to User
```http
POST /api/v1/admin/users/assign-permission
```

**Request Body:**
```json
{
    "user_id": 10,
    "permission_id": 5
}
```

#### Assign Multiple Permissions to User
```http
POST /api/v1/admin/users/assign-multiple-permissions
```

**Request Body:**
```json
{
    "user_id": 10,
    "permission_ids": [5, 6, 7]
}
```

#### Remove Permission from User
```http
POST /api/v1/admin/users/remove-permission
```

#### Remove All Permissions from User
```http
POST /api/v1/admin/users/remove-all-permissions
```

## Security Features

### 1. Role Protection

- **System Roles** (`admin`, `basic_user`, `manager`) cannot be deleted
- **Admin Role** has critical permissions that cannot be removed
- **Last Admin Protection** prevents removing admin role from the last admin user

### 2. Permission Protection

- **Critical Permissions** for admin users cannot be removed
- **System Permissions** are protected from deletion when assigned to roles

### 3. Access Control

- All endpoints require `admin` role
- Middleware protection via `role:admin`
- Sanctum authentication required

### 4. Input Validation

- Comprehensive validation rules for all inputs
- Guard name validation (`api`, `web`)
- Unique constraint validation for names
- Array validation for bulk operations

## Error Handling

### HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

### Error Response Format

```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field": ["Error message"]
    }
}
```

## Performance Features

### 1. Caching

- Role and permission data cached with tags
- Cache invalidation on data changes
- Configurable cache TTL

### 2. Database Optimization

- Efficient queries with proper indexing
- Pagination for large datasets
- Eager loading of relationships

### 3. Bulk Operations

- Multiple role/permission assignment in single request
- Batch processing for large datasets
- Transaction safety for bulk operations

## Testing

### Test Coverage

The RBAC system includes comprehensive testing:

- **Role Management**: 11 tests
- **Permission Management**: 17 tests  
- **Role Permission Management**: 17 tests
- **User Role Assignment**: 17 tests
- **User Permission Management**: 17 tests

**Total: 79 tests with 535 assertions**

### Running Tests

```bash
# Run all RBAC tests
php artisan test --filter="RBAC"

# Run specific component tests
php artisan test tests/Feature/RBAC/RoleManagementTest.php
php artisan test tests/Feature/RBAC/PermissionManagementTest.php
php artisan test tests/Feature/RBAC/RolePermissionManagementTest.php
php artisan test tests/Feature/RBAC/UserRoleAssignmentTest.php
php artisan test tests/Feature/RBAC/UserPermissionManagementTest.php
```

## Configuration

### Environment Variables

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=droxauto_testing
DB_USERNAME=postgres
DB_PASSWORD=admin

# Cache
CACHE_DRIVER=redis
CACHE_TTL=3600

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
SANCTUM_EXPIRATION=60
```

### Permission Configuration

```php
// config/permission.php
'default_guard_name' => 'api',
'models' => [
    'role' => Spatie\Permission\Models\Role::class,
    'permission' => Spatie\Permission\Models\Permission::class,
],
```

## Database Schema

### Roles Table
```sql
CREATE TABLE roles (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Permissions Table
```sql
CREATE TABLE permissions (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Role Has Permissions Table
```sql
CREATE TABLE role_has_permissions (
    permission_id BIGINT,
    role_id BIGINT,
    PRIMARY KEY (permission_id, role_id)
);
```

### Model Has Roles Table
```sql
CREATE TABLE model_has_roles (
    role_id BIGINT,
    model_type VARCHAR(255),
    model_id BIGINT,
    PRIMARY KEY (role_id, model_type, model_id)
);
```

### Model Has Permissions Table
```sql
CREATE TABLE model_has_permissions (
    permission_id BIGINT,
    model_type VARCHAR(255),
    model_id BIGINT,
    PRIMARY KEY (permission_id, model_type, model_id)
);
```

## Usage Examples

### 1. Creating a New Role with Permissions

```php
use App\Models\Role;
use App\Models\Permission;

// Create role
$role = Role::create([
    'name' => 'content_manager',
    'guard_name' => 'api',
    'description' => 'Manages content creation and editing'
]);

// Create permissions
$permissions = [
    'view_content',
    'create_content', 
    'edit_content',
    'delete_content',
    'publish_content'
];

foreach ($permissions as $permissionName) {
    $permission = Permission::create([
        'name' => $permissionName,
        'guard_name' => 'api'
    ]);
    $role->givePermissionTo($permission);
}
```

### 2. Assigning Role to User

```php
use App\Models\User;

$user = User::find(1);
$role = Role::where('name', 'content_manager')->first();

$user->assignRole($role);
```

### 3. Checking Permissions

```php
// Check if user has specific permission
if ($user->hasPermissionTo('edit_content')) {
    // User can edit content
}

// Check if user has role
if ($user->hasRole('content_manager')) {
    // User is a content manager
}

// Get all user permissions
$permissions = $user->getAllPermissions();

// Get direct permissions (not through roles)
$directPermissions = $user->getDirectPermissions();
```

### 4. API Usage Example

```javascript
// Create a new role
const response = await fetch('/api/v1/admin/roles', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        name: 'moderator',
        guard_name: 'api',
        description: 'Forum moderator role',
        permissions: ['view_posts', 'moderate_posts', 'delete_posts']
    })
});

const result = await response.json();
console.log(result.data.role);
```

## Best Practices

### 1. Role Design

- Use descriptive role names
- Keep roles focused on specific responsibilities
- Avoid creating too many roles
- Use hierarchical role structure when appropriate

### 2. Permission Design

- Use consistent naming conventions
- Group related permissions together
- Avoid overly granular permissions
- Document permission purposes

### 3. Security

- Regularly audit role assignments
- Use least privilege principle
- Monitor permission changes
- Implement role approval workflows

### 4. Performance

- Cache frequently accessed permission data
- Use bulk operations for multiple assignments
- Monitor database query performance
- Implement proper indexing

## Troubleshooting

### Common Issues

1. **Permission Denied Errors**
   - Check if user has required role
   - Verify middleware configuration
   - Check guard name consistency

2. **Role Not Found Errors**
   - Verify role exists in database
   - Check guard name matches
   - Ensure proper database seeding

3. **Performance Issues**
   - Check cache configuration
   - Monitor database queries
   - Verify indexing strategy

### Debug Mode

Enable debug mode to get detailed error information:

```env
APP_DEBUG=true
```

## Support

For technical support or questions about the RBAC system:

1. Check the test suite for usage examples
2. Review the API documentation
3. Check Laravel and Spatie Permission documentation
4. Contact the development team

## Changelog

### Version 1.0.0 (Current)
- Complete RBAC system implementation
- All core components implemented and tested
- Comprehensive API endpoints
- Enterprise-grade security features
- Full test coverage (79 tests, 535 assertions)
