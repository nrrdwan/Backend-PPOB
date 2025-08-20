<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class UpdatePermissionRoleToIndonesianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update Permissions to Indonesian
        $permissionUpdates = [
            'view-products' => [
                'name' => 'Lihat Produk',
                'description' => 'Dapat melihat daftar produk'
            ],
            'create-products' => [
                'name' => 'Buat Produk',
                'description' => 'Dapat membuat produk baru'
            ],
            'edit-products' => [
                'name' => 'Edit Produk',
                'description' => 'Dapat mengedit produk yang ada'
            ],
            'delete-products' => [
                'name' => 'Hapus Produk',
                'description' => 'Dapat menghapus produk'
            ],
            'view-transactions' => [
                'name' => 'Lihat Transaksi',
                'description' => 'Dapat melihat daftar transaksi'
            ],
            'process-transactions' => [
                'name' => 'Proses Transaksi',
                'description' => 'Dapat memproses transaksi'
            ],
            'view-roles' => [
                'name' => 'Lihat Role',
                'description' => 'Dapat melihat daftar role'
            ],
            'create-roles' => [
                'name' => 'Buat Role',
                'description' => 'Dapat membuat role baru'
            ],
            'edit-roles' => [
                'name' => 'Edit Role',
                'description' => 'Dapat mengedit role yang ada'
            ],
            'delete-roles' => [
                'name' => 'Hapus Role',
                'description' => 'Dapat menghapus role'
            ]
        ];

        foreach ($permissionUpdates as $slug => $data) {
            Permission::where('slug', $slug)->update([
                'name' => $data['name'],
                'description' => $data['description']
            ]);
        }

        // Update Roles to Indonesian
        $roleUpdates = [
            'admin' => [
                'name' => 'Admin',
                'description' => 'Akses penuh ke semua fitur sistem'
            ],
            'agen' => [
                'name' => 'Agen',
                'description' => 'Dapat mengelola produk dan memproses transaksi'
            ],
            'user' => [
                'name' => 'Pengguna',
                'description' => 'Pengguna reguler dengan akses terbatas'
            ]
        ];

        foreach ($roleUpdates as $slug => $data) {
            Role::where('slug', $slug)->update([
                'name' => $data['name'],
                'description' => $data['description']
            ]);
        }

        $this->command->info('✅ Berhasil mengupdate permissions dan roles ke bahasa Indonesia');
    }
}
