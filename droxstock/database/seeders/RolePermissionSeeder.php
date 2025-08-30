<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Role management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',

            // Permission management
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',

            // Daparto management
            'view dapartos',
            'create dapartos',
            'edit dapartos',
            'delete dapartos',
            'upload csv',
            'view csv status',

            // System access
            'access admin panel',
            'view system stats',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create roles with explicit API guard
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $userRole = Role::create(['name' => 'user', 'guard_name' => 'api']);
        $managerRole = Role::create(['name' => 'manager', 'guard_name' => 'api']);

        // Assign all permissions to admin
        $adminRole->givePermissionTo(Permission::all());

        // Basic user role with minimal permissions (for admin-created users)
        $basicUserRole = Role::create(['name' => 'basic_user', 'guard_name' => 'api']);
        $basicUserRole->givePermissionTo([
            'view dapartos',
            'view csv status',
        ]);

        // User role has no permissions by default
        // Self-registered users get no role and no permissions
        // Only admin-created users get specific roles and permissions

        // Assign manager permissions
        $managerRole->givePermissionTo([
            'view dapartos',
            'create dapartos',
            'edit dapartos',
            'upload csv',
            'view csv status',
            'view users',
            'view system stats',
        ]);

        // Create admin user
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $adminUser->assignRole('admin');

        // Create regular user
        $regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);
        $regularUser->assignRole('user');

        // Create manager user
        $managerUser = User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
        ]);
        $managerUser->assignRole('manager');

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Admin user: admin@example.com / password');
        $this->command->info('Regular user: user@example.com / password');
        $this->command->info('Manager user: manager@example.com / password');
        $this->command->info('Note: Self-registered users get NO roles/permissions');
        $this->command->info('Admin-created users get "basic_user" role by default');
    }
}
