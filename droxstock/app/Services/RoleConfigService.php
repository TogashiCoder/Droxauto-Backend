<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleConfigService
{
    /**
     * Get a system role name by key
     */
    public static function getRole(string $roleKey): string
    {
        return Config::get("roles.system_roles.{$roleKey}", $roleKey);
    }

    /**
     * Get all system roles
     */
    public static function getAllRoles(): array
    {
        return Config::get('roles.system_roles', []);
    }

    /**
     * Get the admin role name
     */
    public static function getAdminRole(): string
    {
        return self::getRole('admin');
    }

    /**
     * Get the manager role name
     */
    public static function getManagerRole(): string
    {
        return self::getRole('manager');
    }

    /**
     * Get the basic user role name
     */
    public static function getBasicUserRole(): string
    {
        return self::getRole('basic_user');
    }

    /**
     * Get the user role name
     */
    public static function getUserRole(): string
    {
        return self::getRole('user');
    }

    /**
     * Get default role for self-registration
     */
    public static function getDefaultSelfRegistrationRole(): string
    {
        $roleKey = Config::get('roles.default_roles.self_registration', 'user');
        return self::getRole($roleKey);
    }

    /**
     * Get default role for admin-created users
     */
    public static function getDefaultAdminCreatedRole(): string
    {
        $roleKey = Config::get('roles.default_roles.admin_created', 'basic_user');
        return self::getRole($roleKey);
    }

        /**
     * Check if a role is protected (cannot be deleted)
     */
    public static function isProtectedRole(string $roleName): bool
    {
        $protectedRoles = Config::get('roles.protected_roles', []);
        $systemRoles = self::getAllRoles();

        // Check if it's in protected list or is a system role
        return in_array($roleName, $protectedRoles) || in_array($roleName, $systemRoles);
    }

    /**
     * Check if a role is a system role (cannot be renamed or deleted)
     */
    public static function isSystemRole(string $roleName): bool
    {
        $systemRoles = self::getAllRoles();
        return in_array($roleName, $systemRoles);
    }

    /**
     * Check if role name change is allowed
     */
    public static function canRenameRole(string $currentRoleName, string $newRoleName): bool
    {
        // Cannot rename to empty string
        if (empty(trim($newRoleName))) {
            return false;
        }

        // Cannot rename system roles
        if (self::isSystemRole($currentRoleName)) {
            return false;
        }

        // Cannot rename to an existing system role name
        if (self::isSystemRole($newRoleName)) {
            return false;
        }

        return true;
    }

    /**
     * Get all system role names (values from config)
     */
    public static function getSystemRoleNames(): array
    {
        return array_values(self::getAllRoles());
    }

    /**
     * Validate role operation and return error message if invalid
     */
    public static function validateRoleOperation(string $operation, string $roleName, ?string $newRoleName = null): ?string
    {
        switch ($operation) {
            case 'delete':
                if (self::isProtectedRole($roleName)) {
                    return "Cannot delete protected system role '{$roleName}'. This role is required for system functionality.";
                }
                break;

            case 'rename':
            case 'update':
                if (!$newRoleName) {
                    return "New role name is required for rename operation.";
                }

                if (!self::canRenameRole($roleName, $newRoleName)) {
                    if (self::isSystemRole($roleName)) {
                        return "Cannot rename system role '{$roleName}'. System roles are protected to maintain functionality.";
                    }
                    if (self::isSystemRole($newRoleName)) {
                        return "Cannot rename to '{$newRoleName}' as it conflicts with a system role name.";
                    }
                }
                break;
        }

        return null; // No error
    }

    /**
     * Get all permissions grouped by category
     */
    public static function getPermissionsByCategory(): array
    {
        return Config::get('roles.permission_categories', []);
    }

    /**
     * Get all permissions as flat array
     */
    public static function getAllPermissions(): array
    {
        $categories = self::getPermissionsByCategory();
        $permissions = [];

        foreach ($categories as $category => $categoryPermissions) {
            $permissions = array_merge($permissions, $categoryPermissions);
        }

        return array_unique($permissions);
    }

    /**
     * Check if user has admin role (dynamic)
     */
    public static function userIsAdmin($user): bool
    {
        return $user->hasRole(self::getAdminRole());
    }

    /**
     * Check if user has manager role (dynamic)
     */
    public static function userIsManager($user): bool
    {
        return $user->hasRole(self::getManagerRole());
    }

    /**
     * Get middleware string for admin role
     */
    public static function getAdminMiddleware(): string
    {
        return 'role:' . self::getAdminRole();
    }

    /**
     * Assign admin role to user
     */
    public static function assignAdminRole($user): void
    {
        $user->assignRole(self::getAdminRole());
    }

    /**
     * Assign manager role to user
     */
    public static function assignManagerRole($user): void
    {
        $user->assignRole(self::getManagerRole());
    }

    /**
     * Assign basic user role to user
     */
    public static function assignBasicUserRole($user): void
    {
        $user->assignRole(self::getBasicUserRole());
    }

    /**
     * Assign user role to user
     */
    public static function assignUserRole($user): void
    {
        $user->assignRole(self::getUserRole());
    }

    /**
     * Assign default role for self-registration
     */
    public static function assignDefaultSelfRegistrationRole($user): void
    {
        $user->assignRole(self::getDefaultSelfRegistrationRole());
    }

    /**
     * Assign default role for admin-created users
     */
    public static function assignDefaultAdminCreatedRole($user): void
    {
        $user->assignRole(self::getDefaultAdminCreatedRole());
    }
}
