<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UpdateUserRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        foreach ($users as $user) {
            switch ($user->role) {
                case 'admin':
                    $user->role = 'Administrator';
                    break;
                case 'agen':
                    $user->role = 'Agen PPOB';
                    break;
                case 'user':
                    $user->role = 'User Biasa';
                    break;
            }
            $user->save();
        }
        
        $this->command->info('✅ User roles updated successfully!');
    }
}
