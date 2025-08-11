<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bill_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('customer_ref', 64); // nomor meter/no pelanggan
            $table->string('inquiry_id', 64)->nullable(); // id dari provider
            $table->decimal('amount', 18, 2);
            $table->decimal('admin_fee', 18, 2)->default(0);
            $table->json('details')->nullable(); // nama pelanggan, periode, denda, dll.
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 20); // valid|expired|failed
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_id','customer_ref','created_at']);
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('serial_no', 64)->nullable();
            $table->string('token_code', 128)->nullable(); // simpan terenkripsi di app layer
            $table->decimal('denom', 18, 2);
            $table->timestamp('delivered_at')->nullable();
            $table->string('delivery_status', 20)->default('pending'); // pending|sent|failed
            $table->timestamps();

            $table->index(['transaction_id','delivered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('bill_inquiries');
    }
};
