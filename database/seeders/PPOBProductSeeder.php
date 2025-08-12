<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class PPOBProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing products
        Product::truncate();

        // Pulsa & Paket Data
        $pulsaProducts = [
            // Telkomsel
            ['code' => 'TSEL5', 'name' => 'Telkomsel Pulsa 5.000', 'provider' => 'Telkomsel', 'type' => 'pulsa', 'price' => 5500, 'admin_fee' => 500, 'selling_price' => 6000],
            ['code' => 'TSEL10', 'name' => 'Telkomsel Pulsa 10.000', 'provider' => 'Telkomsel', 'type' => 'pulsa', 'price' => 10500, 'admin_fee' => 500, 'selling_price' => 11000],
            ['code' => 'TSEL20', 'name' => 'Telkomsel Pulsa 20.000', 'provider' => 'Telkomsel', 'type' => 'pulsa', 'price' => 20000, 'admin_fee' => 500, 'selling_price' => 20500],
            ['code' => 'TSEL50', 'name' => 'Telkomsel Pulsa 50.000', 'provider' => 'Telkomsel', 'type' => 'pulsa', 'price' => 49500, 'admin_fee' => 500, 'selling_price' => 50000],
            ['code' => 'TSEL100', 'name' => 'Telkomsel Pulsa 100.000', 'provider' => 'Telkomsel', 'type' => 'pulsa', 'price' => 98500, 'admin_fee' => 500, 'selling_price' => 99000],
            
            // Indosat
            ['code' => 'ISAT5', 'name' => 'Indosat Pulsa 5.000', 'provider' => 'Indosat', 'type' => 'pulsa', 'price' => 5500, 'admin_fee' => 500, 'selling_price' => 6000],
            ['code' => 'ISAT10', 'name' => 'Indosat Pulsa 10.000', 'provider' => 'Indosat', 'type' => 'pulsa', 'price' => 10500, 'admin_fee' => 500, 'selling_price' => 11000],
            ['code' => 'ISAT25', 'name' => 'Indosat Pulsa 25.000', 'provider' => 'Indosat', 'type' => 'pulsa', 'price' => 24500, 'admin_fee' => 500, 'selling_price' => 25000],
            
            // XL
            ['code' => 'XL5', 'name' => 'XL Pulsa 5.000', 'provider' => 'XL', 'type' => 'pulsa', 'price' => 5500, 'admin_fee' => 500, 'selling_price' => 6000],
            ['code' => 'XL10', 'name' => 'XL Pulsa 10.000', 'provider' => 'XL', 'type' => 'pulsa', 'price' => 10500, 'admin_fee' => 500, 'selling_price' => 11000],
            ['code' => 'XL30', 'name' => 'XL Pulsa 30.000', 'provider' => 'XL', 'type' => 'pulsa', 'price' => 29500, 'admin_fee' => 500, 'selling_price' => 30000],
        ];

        // PLN (Listrik)
        $plnProducts = [
            ['code' => 'PLN20', 'name' => 'PLN Token 20.000', 'provider' => 'PLN', 'type' => 'pln', 'price' => 20000, 'admin_fee' => 1500, 'selling_price' => 21500],
            ['code' => 'PLN50', 'name' => 'PLN Token 50.000', 'provider' => 'PLN', 'type' => 'pln', 'price' => 50000, 'admin_fee' => 1500, 'selling_price' => 51500],
            ['code' => 'PLN100', 'name' => 'PLN Token 100.000', 'provider' => 'PLN', 'type' => 'pln', 'price' => 100000, 'admin_fee' => 1500, 'selling_price' => 101500],
            ['code' => 'PLN200', 'name' => 'PLN Token 200.000', 'provider' => 'PLN', 'type' => 'pln', 'price' => 200000, 'admin_fee' => 1500, 'selling_price' => 201500],
            ['code' => 'PLN500', 'name' => 'PLN Token 500.000', 'provider' => 'PLN', 'type' => 'pln', 'price' => 500000, 'admin_fee' => 1500, 'selling_price' => 501500],
        ];

        // E-Money
        $emoneyProducts = [
            // GoPay
            ['code' => 'GOPAY10', 'name' => 'GoPay 10.000', 'provider' => 'GoPay', 'type' => 'emoney', 'price' => 10000, 'admin_fee' => 500, 'selling_price' => 10500],
            ['code' => 'GOPAY25', 'name' => 'GoPay 25.000', 'provider' => 'GoPay', 'type' => 'emoney', 'price' => 25000, 'admin_fee' => 500, 'selling_price' => 25500],
            ['code' => 'GOPAY50', 'name' => 'GoPay 50.000', 'provider' => 'GoPay', 'type' => 'emoney', 'price' => 50000, 'admin_fee' => 500, 'selling_price' => 50500],
            
            // OVO
            ['code' => 'OVO10', 'name' => 'OVO 10.000', 'provider' => 'OVO', 'type' => 'emoney', 'price' => 10000, 'admin_fee' => 500, 'selling_price' => 10500],
            ['code' => 'OVO25', 'name' => 'OVO 25.000', 'provider' => 'OVO', 'type' => 'emoney', 'price' => 25000, 'admin_fee' => 500, 'selling_price' => 25500],
            ['code' => 'OVO100', 'name' => 'OVO 100.000', 'provider' => 'OVO', 'type' => 'emoney', 'price' => 100000, 'admin_fee' => 500, 'selling_price' => 100500],
            
            // DANA
            ['code' => 'DANA10', 'name' => 'DANA 10.000', 'provider' => 'DANA', 'type' => 'emoney', 'price' => 10000, 'admin_fee' => 500, 'selling_price' => 10500],
            ['code' => 'DANA25', 'name' => 'DANA 25.000', 'provider' => 'DANA', 'type' => 'emoney', 'price' => 25000, 'admin_fee' => 500, 'selling_price' => 25500],
            ['code' => 'DANA50', 'name' => 'DANA 50.000', 'provider' => 'DANA', 'type' => 'emoney', 'price' => 50000, 'admin_fee' => 500, 'selling_price' => 50500],
        ];

        // Game Vouchers
        $gameProducts = [
            // Mobile Legends
            ['code' => 'ML86', 'name' => 'Mobile Legends 86 Diamonds', 'provider' => 'Mobile Legends', 'type' => 'game', 'price' => 20000, 'admin_fee' => 1000, 'selling_price' => 21000],
            ['code' => 'ML172', 'name' => 'Mobile Legends 172 Diamonds', 'provider' => 'Mobile Legends', 'type' => 'game', 'price' => 40000, 'admin_fee' => 1000, 'selling_price' => 41000],
            ['code' => 'ML257', 'name' => 'Mobile Legends 257 Diamonds', 'provider' => 'Mobile Legends', 'type' => 'game', 'price' => 60000, 'admin_fee' => 1000, 'selling_price' => 61000],
            
            // Free Fire
            ['code' => 'FF70', 'name' => 'Free Fire 70 Diamonds', 'provider' => 'Free Fire', 'type' => 'game', 'price' => 10000, 'admin_fee' => 1000, 'selling_price' => 11000],
            ['code' => 'FF140', 'name' => 'Free Fire 140 Diamonds', 'provider' => 'Free Fire', 'type' => 'game', 'price' => 20000, 'admin_fee' => 1000, 'selling_price' => 21000],
            ['code' => 'FF355', 'name' => 'Free Fire 355 Diamonds', 'provider' => 'Free Fire', 'type' => 'game', 'price' => 50000, 'admin_fee' => 1000, 'selling_price' => 51000],
            
            // Google Play
            ['code' => 'GP10', 'name' => 'Google Play Gift Card 10.000', 'provider' => 'Google Play', 'type' => 'game', 'price' => 10000, 'admin_fee' => 1000, 'selling_price' => 11000],
            ['code' => 'GP25', 'name' => 'Google Play Gift Card 25.000', 'provider' => 'Google Play', 'type' => 'game', 'price' => 25000, 'admin_fee' => 1000, 'selling_price' => 26000],
            ['code' => 'GP50', 'name' => 'Google Play Gift Card 50.000', 'provider' => 'Google Play', 'type' => 'game', 'price' => 50000, 'admin_fee' => 1000, 'selling_price' => 51000],
        ];

        // PDAM
        $pdamProducts = [
            ['code' => 'PDAM_JKT', 'name' => 'PDAM Jakarta', 'provider' => 'PDAM DKI Jakarta', 'type' => 'pdam', 'price' => 0, 'admin_fee' => 2500, 'selling_price' => 2500],
            ['code' => 'PDAM_BDG', 'name' => 'PDAM Bandung', 'provider' => 'PDAM Bandung', 'type' => 'pdam', 'price' => 0, 'admin_fee' => 2500, 'selling_price' => 2500],
            ['code' => 'PDAM_SBY', 'name' => 'PDAM Surabaya', 'provider' => 'PDAM Surabaya', 'type' => 'pdam', 'price' => 0, 'admin_fee' => 2500, 'selling_price' => 2500],
        ];

        // Other services
        $otherProducts = [
            ['code' => 'BPJS_KES', 'name' => 'BPJS Kesehatan', 'provider' => 'BPJS', 'type' => 'other', 'price' => 0, 'admin_fee' => 2500, 'selling_price' => 2500],
            ['code' => 'INDIHOME', 'name' => 'IndiHome', 'provider' => 'Telkom', 'type' => 'other', 'price' => 0, 'admin_fee' => 2500, 'selling_price' => 2500],
            ['code' => 'FIRSTMEDIA', 'name' => 'First Media', 'provider' => 'First Media', 'type' => 'other', 'price' => 0, 'admin_fee' => 2500, 'selling_price' => 2500],
        ];

        // Combine all products
        $allProducts = array_merge($pulsaProducts, $plnProducts, $emoneyProducts, $gameProducts, $pdamProducts, $otherProducts);

        // Add common fields and insert
        foreach ($allProducts as $product) {
            Product::create(array_merge($product, [
                'description' => 'Layanan ' . $product['name'],
                'is_active' => true,
                'stock' => 999,
                'is_unlimited' => true,
                'settings' => null
            ]));
        }

        $this->command->info('PPOB Products seeded successfully! Total: ' . count($allProducts) . ' products');
    }
}
