<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class ShowUpdatedDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('=== PERMISSIONS (BAHASA INDONESIA) ===');
        
        $permissions = Permission::select('name', 'slug', 'group', 'description')->get();
        foreach ($permissions as $permission) {
            $this->command->line("✅ {$permission->name} ({$permission->slug}) - {$permission->description}");
        }
        
        $this->command->info('');
        $this->command->info('=== ROLES (BAHASA INDONESIA) ===');
        
        $roles = Role::select('name', 'slug', 'description')->get();
        foreach ($roles as $role) {
            $this->command->line("🔑 {$role->name} ({$role->slug}) - {$role->description}");
        }
        
        $this->command->info('');
        $this->command->info('=== TOTAL PERMISSIONS PER GROUP ===');
        
        $groups = Permission::selectRaw('"group", COUNT(*) as total')->groupBy('group')->get();
        foreach ($groups as $group) {
            $this->command->line("📁 {$group->group}: {$group->total} permissions");
        }
    }
}
