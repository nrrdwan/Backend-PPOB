<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type', 20)->default('aggregator'); // aggregator|bank
            $table->string('status', 20)->default('active');
            $table->json('settings')->nullable(); // endpoint, api_key, dsb.
            $table->timestamps();
        });

        Schema::create('product_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('providers')->restrictOnDelete();
            $table->string('provider_code')->nullable(); // code produk di provider
            $table->boolean('is_active')->default(true);
            $table->json('pricing_formula')->nullable(); // markup, fee rules
            $table->timestamps();

            $table->unique(['product_id','provider_id'], 'product_provider_unique');
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->index();
            $table->string('type', 20)->index(); // pdam, telco_region, dll
            $table->foreignId('parent_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('product_areas', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->primary(['product_id','area_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_areas');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('product_providers');
        Schema::dropIfExists('providers');
    }
};
