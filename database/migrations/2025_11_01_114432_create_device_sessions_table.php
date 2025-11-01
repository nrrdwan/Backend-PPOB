<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token_id')->unique(); // Sanctum token ID
            $table->string('device_name');
            $table->string('device_type')->nullable(); // mobile, web, desktop
            $table->string('os_version')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('browser')->nullable();
            $table->string('ip_address', 45);
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'last_active_at']);
            $table->index('token_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_sessions');
    }
};