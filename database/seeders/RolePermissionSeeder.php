<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // Admin Panel Access
            ['name' => 'Access Admin Panel', 'slug' => 'access-admin-panel', 'group' => 'admin', 'description' => 'Can access admin panel'],
            
            // User Management
            ['name' => 'View Users', 'slug' => 'view-users', 'group' => 'user', 'description' => 'Can view user list'],
            ['name' => 'Create Users', 'slug' => 'create-users', 'group' => 'user', 'description' => 'Can create new users'],
            ['name' => 'Edit Users', 'slug' => 'edit-users', 'group' => 'user', 'description' => 'Can edit existing users'],
            ['name' => 'Delete Users', 'slug' => 'delete-users', 'group' => 'user', 'description' => 'Can delete users'],
            
            // Product Management
            ['name' => 'View Products', 'slug' => 'view-products', 'group' => 'product', 'description' => 'Can view product list'],
            ['name' => 'Create Products', 'slug' => 'create-products', 'group' => 'product', 'description' => 'Can create new products'],
            ['name' => 'Edit Products', 'slug' => 'edit-products', 'group' => 'product', 'description' => 'Can edit existing products'],
            ['name' => 'Delete Products', 'slug' => 'delete-products', 'group' => 'product', 'description' => 'Can delete products'],
            
            // Transaction Management
            ['name' => 'View Transactions', 'slug' => 'view-transactions', 'group' => 'transaction', 'description' => 'Can view transaction list'],
            ['name' => 'Process Transactions', 'slug' => 'process-transactions', 'group' => 'transaction', 'description' => 'Can process transactions'],
            
            // Role Management
            ['name' => 'View Roles', 'slug' => 'view-roles', 'group' => 'role', 'description' => 'Can view role list'],
            ['name' => 'Create Roles', 'slug' => 'create-roles', 'group' => 'role', 'description' => 'Can create new roles'],
            ['name' => 'Edit Roles', 'slug' => 'edit-roles', 'group' => 'role', 'description' => 'Can edit existing roles'],
            ['name' => 'Delete Roles', 'slug' => 'delete-roles', 'group' => 'role', 'description' => 'Can delete roles'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Create 3 Basic Roles
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Full access to all features',
                'permissions' => [
                    'access-admin-panel',
                    'view-users', 'create-users', 'edit-users', 'delete-users',
                    'view-products', 'create-products', 'edit-products', 'delete-products',
                    'view-transactions', 'process-transactions',
                    'view-roles', 'create-roles', 'edit-roles', 'delete-roles',
                ]
            ],
            [
                'name' => 'Agen PPOB',
                'slug' => 'agen',
                'description' => 'Can manage products and process transactions',
                'permissions' => [
                    'access-admin-panel',
                    'view-products', 'edit-products',
                    'view-transactions', 'process-transactions',
                ]
            ],
            [
                'name' => 'User Biasa',
                'slug' => 'user',
                'description' => 'Regular user with limited access',
                'permissions' => [
                    // No admin panel access for regular users
                ]
            ],
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'is_active' => true,
                ]
            );

            // Attach permissions to role
            $permissions = Permission::whereIn('slug', $roleData['permissions'])->get();
            $role->permissions()->sync($permissions->pluck('id')->toArray());
        }

        echo "✅ Roles and Permissions seeded successfully!\n";
        echo "📝 Created Roles: Administrator, Agen PPOB, User Biasa\n";
        echo "🔑 Created " . count($permissions) . " permissions\n";
    }
}
