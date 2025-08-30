# Permission Structure Documentation

## Overview

This document explains the updated permission structure where self-registered users have NO permissions, and only admin-created users get specific roles and permissions.

## User Types & Permissions

### 1. Self-Registered Users (Public Registration)

**Endpoint:** `POST /api/v1/auth/register`
**Role:** None assigned
**Permissions:** None (completely restricted)
**Access Level:** Cannot access any protected endpoints
**Purpose:** Account creation only, requires admin approval for access

**What they CAN do:**

-   ✅ Create account
-   ✅ Login/logout
-   ✅ Refresh token
-   ✅ View their own profile

**What they CANNOT do:**

-   ❌ View dapartos
-   ❌ Access any protected API endpoints
-   ❌ Upload files
-   ❌ View system data

### 2. Admin-Created Users

**Endpoint:** `POST /api/v1/admin/users`
**Role:** Assigned by admin
**Permissions:** Based on assigned role
**Access Level:** Determined by admin
**Purpose:** Staff/employee accounts with specific access levels

#### Default Role: `basic_user`

**Permissions:**

-   ✅ `view dapartos` - Browse parts inventory
-   ✅ `view csv status` - Check CSV processing status

#### Other Available Roles:

-   **`manager`** - Enhanced permissions for managers
-   **`admin`** - Full system access
-   **Custom roles** - Created by admin as needed

## Security Benefits

1. **Zero Default Access** - Self-registered users pose no security risk
2. **Admin Control** - Only authorized personnel get access
3. **Granular Permissions** - Admins can fine-tune access levels
4. **Audit Trail** - Clear record of who granted what access

## Workflow

### For Customers:

1. User registers via `/api/v1/auth/register`
2. Account created with NO permissions
3. User cannot access any protected features
4. Admin must manually grant access if needed

### For Staff:

1. Admin creates user via `/api/v1/admin/users`
2. Admin assigns appropriate role(s)
3. User gets permissions based on role
4. User can access authorized features immediately

## API Endpoints Access

| Endpoint                  | Self-Registered | Basic User | Manager | Admin |
| ------------------------- | --------------- | ---------- | ------- | ----- |
| `/api/v1/auth/me`         | ✅              | ✅         | ✅      | ✅    |
| `/api/v1/dapartos`        | ❌              | ✅         | ✅      | ✅    |
| `/api/v1/dapartos/create` | ❌              | ❌         | ✅      | ✅    |
| `/api/v1/admin/users`     | ❌              | ❌         | ❌      | ✅    |

## Migration Notes

If you have existing users with the old 'user' role, you may need to:

1. Run the updated seeder: `php artisan db:seed --class=RolePermissionSeeder`
2. Manually assign appropriate roles to existing users
3. Consider creating a migration script for existing users

## Best Practices

1. **Always assign roles explicitly** when creating users via admin
2. **Use the principle of least privilege** - only grant necessary permissions
3. **Regularly audit user permissions** to ensure security
4. **Document role purposes** for team clarity
