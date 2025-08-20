<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductCommission;

class ProductCommissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first few products to add sample commissions
        $products = Product::take(3)->get();

        foreach ($products as $product) {
            ProductCommission::create([
                'product_id' => $product->id,
                'seller_commission' => 5.0,
                'seller_commission_type' => 'percent',
                'reseller_commission' => 10.0,
                'reseller_commission_type' => 'percent',
                'b2b_commission' => 15.0,
                'b2b_commission_type' => 'percent',
                'min_commission' => 1000.0,
                'max_commission' => 50000.0,
                'is_active' => true,
            ]);
        }

        // Add one with fixed commission
        if ($products->count() > 1) {
            ProductCommission::create([
                'product_id' => $products[1]->id,
                'seller_commission' => 2000.0,
                'seller_commission_type' => 'fixed',
                'reseller_commission' => 5000.0,
                'reseller_commission_type' => 'fixed',
                'b2b_commission' => 10000.0,
                'b2b_commission_type' => 'fixed',
                'is_active' => true,
            ]);
        }
    }
}
