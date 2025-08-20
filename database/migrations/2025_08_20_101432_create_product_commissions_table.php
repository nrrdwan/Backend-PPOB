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
        Schema::create('product_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            // Komisi untuk Seller (dalam persen atau nominal)
            $table->decimal('seller_commission', 10, 2)->default(0);
            $table->enum('seller_commission_type', ['percent', 'fixed'])->default('percent');
            
            // Komisi untuk Reseller (dalam persen atau nominal)
            $table->decimal('reseller_commission', 10, 2)->default(0);
            $table->enum('reseller_commission_type', ['percent', 'fixed'])->default('percent');
            
            // Komisi untuk B2B (dalam persen atau nominal)
            $table->decimal('b2b_commission', 10, 2)->default(0);
            $table->enum('b2b_commission_type', ['percent', 'fixed'])->default('percent');
            
            // Status aktif/nonaktif komisi
            $table->boolean('is_active')->default(true);
            
            // Minimum dan maksimum komisi (opsional)
            $table->decimal('min_commission', 10, 2)->nullable();
            $table->decimal('max_commission', 10, 2)->nullable();
            
            $table->timestamps();
            
            // Index untuk performa
            $table->index(['product_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_commissions');
    }
};
