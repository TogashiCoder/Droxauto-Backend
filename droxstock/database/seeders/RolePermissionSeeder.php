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
            'approve user registrations',
            'view profile', // Permission to view own profile

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
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api'],
                ['name' => $permission, 'guard_name' => 'api']
            );
        }

        // Create roles with explicit API guard (dynamic)
        $adminRoleName = \App\Services\RoleConfigService::getAdminRole();
        $userRoleName = \App\Services\RoleConfigService::getUserRole();
        $managerRoleName = \App\Services\RoleConfigService::getManagerRole();
        $basicUserRoleName = \App\Services\RoleConfigService::getBasicUserRole();

        $adminRole = Role::firstOrCreate(['name' => $adminRoleName, 'guard_name' => 'api']);
        $userRole = Role::firstOrCreate(['name' => $userRoleName, 'guard_name' => 'api']);
        $managerRole = Role::firstOrCreate(['name' => $managerRoleName, 'guard_name' => 'api']);
        $basicUserRole = Role::firstOrCreate(['name' => $basicUserRoleName, 'guard_name' => 'api']);

        // Assign all permissions to admin
        $adminRole->syncPermissions(Permission::all());
        $basicUserRole->syncPermissions([
            'view dapartos',
            'view csv status',
            'view profile', // All users can view their own profile
        ]);

        // User role has no permissions by default
        // Self-registered users get no role and no permissions by default
        // Only admin-created users get specific roles and permissions
        // NOTE: Self-registered users will be given 'view profile' permission directly in UserController

        // Assign manager permissions
        $managerRole->syncPermissions([
            'view dapartos',
            'create dapartos',
            'edit dapartos',
            'upload csv',
            'view csv status',
            'view users',
            'view system stats',
            'view profile', // All users can view their own profile
        ]);

        // Create admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'silverproduction2023@gmail.com'],
            [
                'name' => 'Hassan Admin',
                'email' => 'silverproduction2023@gmail.com',
                'password' => Hash::make('droxauto_superadmin@2025'),
                'registration_status' => 'approved',
                'registration_date' => now(),
                'approved_at' => now(),
            ]
        );
        $adminUser->syncRoles([$adminRoleName]);

        // Create regular user
        $regularUser = User::firstOrCreate(
            ['email' => 'daparto@platforme.com'],
            [
                'name' => 'Daparto',
                'email' => 'daparto@platforme.com',
                'password' => Hash::make('daparto_Q8r!Z5vH2n'),
                'registration_status' => 'approved',
                'registration_date' => now(),
                'approved_at' => now(),
            ]
        );
        $regularUser->syncRoles([$userRoleName]);

        // Create manager user
        $managerUser = User::firstOrCreate(
            ['email' => 'taoufik.b.pro@gmail.com'],
            [
                'name' => 'Manager Taoufik',
                'email' => 'taoufik.b.pro@gmail.com',
                'password' => Hash::make('Taoufik123@///@2020'),
                'registration_status' => 'approved',
                'registration_date' => now(),
                'approved_at' => now(),
            ]
        );
        $managerUser->syncRoles([$managerRoleName]);

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Admin user: droxauto@gmail.com / droxauto_superadmin@2025');
        $this->command->info('Regular user: daparto@platforme.com / daparto_Q8r!Z5vH2n');
        $this->command->info('Manager user: taoufik.b.pro@gmail.com / Taoufik123@///@2020');
        $this->command->info('Note: Self-registered users get NO roles/permissions');
        $this->command->info('Admin-created users get "basic_user" role by default');
    }
}
