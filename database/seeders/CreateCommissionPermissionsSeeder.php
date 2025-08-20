<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class CreateCommissionPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commissionPermissions = [
            [
                'name' => 'Lihat Komisi Produk',
                'slug' => 'view-product-commissions',
                'description' => 'Dapat melihat daftar komisi produk',
                'group' => 'commission',
                'is_active' => true,
            ],
            [
                'name' => 'Buat Komisi Produk',
                'slug' => 'create-product-commissions',
                'description' => 'Dapat membuat komisi produk baru',
                'group' => 'commission',
                'is_active' => true,
            ],
            [
                'name' => 'Edit Komisi Produk',
                'slug' => 'edit-product-commissions',
                'description' => 'Dapat mengedit komisi produk yang ada',
                'group' => 'commission',
                'is_active' => true,
            ],
            [
                'name' => 'Hapus Komisi Produk',
                'slug' => 'delete-product-commissions',
                'description' => 'Dapat menghapus komisi produk',
                'group' => 'commission',
                'is_active' => true,
            ],
        ];

        foreach ($commissionPermissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $this->command->info('✅ Berhasil membuat permissions untuk sistem komisi');
    }
}
