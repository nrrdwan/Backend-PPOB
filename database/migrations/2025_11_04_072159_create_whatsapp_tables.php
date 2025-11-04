<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table untuk menyimpan WhatsApp Group Links
        Schema::create('whatsapp_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama group: "Group Modipay Official"
            $table->text('link'); // https://chat.whatsapp.com/...
            $table->enum('type', ['main_group', 'vip_group', 'reseller_group', 'info_group'])
                  ->default('main_group'); // Tipe group
            $table->text('description')->nullable(); // Deskripsi group
            $table->boolean('is_active')->default(true); // Status aktif/non-aktif
            $table->integer('click_count')->default(0); // Total klik
            $table->timestamps();
            
            $table->index('type');
            $table->index('is_active');
        });

        // Table untuk menyimpan kontak admin WhatsApp
        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama: "Customer Service"
            $table->string('phone_number'); // 6285876887902
            $table->enum('type', ['customer_service', 'technical_support', 'sales'])
                  ->default('customer_service');
            $table->text('default_message')->nullable(); // Pesan default
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('type');
            $table->index('is_active');
        });

        // Table untuk tracking klik group link (analytics)
        Schema::create('whatsapp_group_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('group_id')->constrained('whatsapp_groups')->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('clicked_at');
            
            $table->index('user_id');
            $table->index('group_id');
            $table->index('clicked_at');
        });

        // Table untuk tracking klik kontak admin (analytics)
        Schema::create('whatsapp_contact_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('contact_id')->constrained('whatsapp_contacts')->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('clicked_at');
            
            $table->index('user_id');
            $table->index('contact_id');
            $table->index('clicked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_contact_clicks');
        Schema::dropIfExists('whatsapp_group_clicks');
        Schema::dropIfExists('whatsapp_contacts');
        Schema::dropIfExists('whatsapp_groups');
    }
};