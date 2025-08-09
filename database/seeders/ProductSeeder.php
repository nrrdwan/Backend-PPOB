<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            // Pulsa
            [
                'code' => 'TSEL5K',
                'name' => 'Telkomsel 5.000',
                'provider' => 'Telkomsel',
                'type' => 'pulsa',
                'price' => 5000,
                'admin_fee' => 500,
                'selling_price' => 5500,
                'description' => 'Pulsa Telkomsel 5.000',
                'is_active' => true,
                'stock' => 1000,
                'is_unlimited' => true,
            ],
            [
                'code' => 'TSEL10K',
                'name' => 'Telkomsel 10.000',
                'provider' => 'Telkomsel',
                'type' => 'pulsa',
                'price' => 10000,
                'admin_fee' => 750,
                'selling_price' => 10750,
                'description' => 'Pulsa Telkomsel 10.000',
                'is_active' => true,
                'stock' => 1000,
                'is_unlimited' => true,
            ],
            [
                'code' => 'XL5K',
                'name' => 'XL 5.000',
                'provider' => 'XL',
                'type' => 'pulsa',
                'price' => 5000,
                'admin_fee' => 500,
                'selling_price' => 5500,
                'description' => 'Pulsa XL 5.000',
                'is_active' => true,
                'stock' => 1000,
                'is_unlimited' => true,
            ],
            
            // Paket Data
            [
                'code' => 'TSEL1GB',
                'name' => 'Telkomsel 1GB',
                'provider' => 'Telkomsel',
                'type' => 'data',
                'price' => 15000,
                'admin_fee' => 1000,
                'selling_price' => 16000,
                'description' => 'Paket Data Telkomsel 1GB',
                'is_active' => true,
                'stock' => 500,
                'is_unlimited' => true,
            ],
            [
                'code' => 'ISAT2GB',
                'name' => 'Indosat 2GB',
                'provider' => 'Indosat',
                'type' => 'data',
                'price' => 25000,
                'admin_fee' => 1500,
                'selling_price' => 26500,
                'description' => 'Paket Data Indosat 2GB',
                'is_active' => true,
                'stock' => 500,
                'is_unlimited' => true,
            ],
            
            // PLN
            [
                'code' => 'PLN20K',
                'name' => 'Token PLN 20.000',
                'provider' => 'PLN',
                'type' => 'pln',
                'price' => 20000,
                'admin_fee' => 1500,
                'selling_price' => 21500,
                'description' => 'Token Listrik PLN 20.000',
                'is_active' => true,
                'stock' => 0,
                'is_unlimited' => true,
            ],
            [
                'code' => 'PLN50K',
                'name' => 'Token PLN 50.000',
                'provider' => 'PLN',
                'type' => 'pln',
                'price' => 50000,
                'admin_fee' => 2500,
                'selling_price' => 52500,
                'description' => 'Token Listrik PLN 50.000',
                'is_active' => true,
                'stock' => 0,
                'is_unlimited' => true,
            ],
            
            // Game
            [
                'code' => 'MLBB50',
                'name' => 'Mobile Legends 50 Diamond',
                'provider' => 'Moonton',
                'type' => 'game',
                'price' => 15000,
                'admin_fee' => 1000,
                'selling_price' => 16000,
                'description' => '50 Diamond Mobile Legends',
                'is_active' => true,
                'stock' => 100,
                'is_unlimited' => false,
            ],
            [
                'code' => 'FF100',
                'name' => 'Free Fire 100 Diamond',
                'provider' => 'Garena',
                'type' => 'game',
                'price' => 15000,
                'admin_fee' => 1000,
                'selling_price' => 16000,
                'description' => '100 Diamond Free Fire',
                'is_active' => true,
                'stock' => 100,
                'is_unlimited' => false,
            ],
            
            // E-Money
            [
                'code' => 'GOPAY10K',
                'name' => 'GoPay 10.000',
                'provider' => 'Gojek',
                'type' => 'emoney',
                'price' => 10000,
                'admin_fee' => 1000,
                'selling_price' => 11000,
                'description' => 'Top Up GoPay 10.000',
                'is_active' => true,
                'stock' => 0,
                'is_unlimited' => true,
            ],
            [
                'code' => 'OVO25K',
                'name' => 'OVO 25.000',
                'provider' => 'OVO',
                'type' => 'emoney',
                'price' => 25000,
                'admin_fee' => 1500,
                'selling_price' => 26500,
                'description' => 'Top Up OVO 25.000',
                'is_active' => true,
                'stock' => 0,
                'is_unlimited' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
