<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class AssignCommissionPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Admin role
        $adminRole = Role::where('slug', 'admin')->first();
        
        if ($adminRole) {
            // Get all commission permissions
            $commissionPermissions = Permission::where('group', 'commission')->get();
            
            foreach ($commissionPermissions as $permission) {
                $adminRole->givePermission($permission);
            }
            
            $this->command->info('✅ Permissions komisi berhasil diberikan ke role Admin');
        } else {
            $this->command->error('❌ Role Admin tidak ditemukan');
        }
    }
}
