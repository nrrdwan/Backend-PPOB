<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::create('about_us', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique(); // 'group_modipay', 'whatsapp_admin', 'instagram'
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('link')->nullable(); // URL/Link
            $table->string('icon_path')->nullable(); // Icon path
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0); // Untuk sorting
            $table->timestamps();
            
            // Index untuk performa
            $table->index(['is_active', 'order']);
        });

        // Insert default data
        DB::table('about_us')->insert([
            [
                'type' => 'group_modipay',
                'title' => 'Group Modipay',
                'description' => 'Bergabung dengan komunitas Modipay',
                'link' => 'https://t.me/modipaygroup',
                'icon_path' => null,
                'is_active' => true,
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'whatsapp_admin',
                'title' => 'Whatsapp Admin',
                'description' => 'Hubungi admin melalui WhatsApp',
                'link' => 'https://wa.me/6281234567890',
                'icon_path' => null,
                'is_active' => true,
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'instagram',
                'title' => 'Instagram',
                'description' => 'Follow Instagram kami',
                'link' => 'https://www.instagram.com/yourlilsan/',
                'icon_path' => null,
                'is_active' => true,
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('about_us');
    }
};