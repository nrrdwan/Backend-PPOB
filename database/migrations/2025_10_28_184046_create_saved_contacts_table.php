<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('phone_number');
            $table->string('provider');
            $table->string('name')->nullable(); // ✅ Tambahkan kolom name
            $table->boolean('is_favorite')->default(false); // ✅ Tambahkan kolom is_favorite
            $table->timestamps();
            
            // ✅ Index untuk performa
            $table->index(['user_id', 'phone_number', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_contacts');
    }
};