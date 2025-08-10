<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@ppob.com'],
            [
                'name' => 'PPOB Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'Admin', // Updated role name to match roles table
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $admin->roles()->sync([$adminRole->id]);
        }

        // Create Agen User  
        $agen = User::firstOrCreate(
            ['email' => 'agent@ppob.com'],
            [
                'name' => 'PPOB Agent',
                'password' => Hash::make('agent123'),
                'role' => 'Agen PPOB', // Updated role name
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Assign agen role
        $agenRole = Role::where('slug', 'agen')->first();
        if ($agenRole) {
            $agen->roles()->sync([$agenRole->id]);
        }

        // Create Demo Regular User
        $user = User::firstOrCreate(
            ['email' => 'user@ppob.com'],
            [
                'name' => 'PPOB User Demo',
                'password' => Hash::make('user123'),
                'role' => 'User Biasa', // Updated role name
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Assign user role
        $userRole = Role::where('slug', 'user')->first();
        if ($userRole) {
            $user->roles()->sync([$userRole->id]);
        }

        echo "✅ Default users created successfully!\n";
        echo "👤 Admin: admin@ppob.com / admin123\n";
        echo "👤 Agent: agent@ppob.com / agent123\n";
        echo "👤 User: user@ppob.com / user123\n";
    }
}
