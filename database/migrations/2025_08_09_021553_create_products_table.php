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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('provider');
            $table->enum('type', ['pulsa', 'data', 'pln', 'pdam', 'game', 'emoney', 'other'])->default('other');
            $table->decimal('price', 15, 2);
            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('stock')->default(0);
            $table->boolean('is_unlimited')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index(['provider', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
