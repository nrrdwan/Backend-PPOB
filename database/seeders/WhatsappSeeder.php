<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WhatsappSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed WhatsApp Groups
        DB::table('whatsapp_groups')->insert([
            [
                'name' => 'Group Modipay Official',
                'link' => 'https://chat.whatsapp.com/GYMZyT48AbNA2GKJsogL7j?mode=wwt',
                'type' => 'main_group',
                'description' => 'Group resmi Merah Putih Pay untuk info produk, update harga, dan diskusi member',
                'is_active' => true,
                'click_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Group VIP Member',
                'link' => 'https://chat.whatsapp.com/Dx5hhFGbEVDEyQpoqqDIV2?mode=wwt',
                'type' => 'vip_group',
                'description' => 'Group khusus member VIP dengan benefit eksklusif',
                'is_active' => true,
                'click_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Group Reseller',
                'link' => 'https://chat.whatsapp.com/Cl9LaZN0DdZ3qJ7GPUPWoX?mode=wwt',
                'type' => 'reseller_group',
                'description' => 'Group khusus reseller Merah Putih Pay',
                'is_active' => false,
                'click_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Seed WhatsApp Contacts
        DB::table('whatsapp_contacts')->insert([
            [
                'name' => 'Customer Service',
                'phone_number' => '6285876887902', // ⚠️ NOMOR CS KAMU
                'type' => 'customer_service',
                'default_message' => 'Halo Admin, saya butuh bantuan',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Technical Support',
                'phone_number' => '6222297829991',
                'type' => 'technical_support',
                'default_message' => 'Halo Tim Support, saya mengalami kendala teknis',
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        echo "✅ WhatsApp Groups & Contacts seeded successfully!\n";
    }
}