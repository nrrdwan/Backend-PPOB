<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('provider', 30)->default('midtrans');
            $table->string('channel', 30); // va_bca, va_bri, qris, gopay, cc, dll
            $table->string('request_id', 64); // idempotency per request
            $table->string('external_payment_id', 64)->unique(); // order_id / transaksi di gateway
            $table->string('status', 20); // pending|paid|failed|expired|canceled
            $table->decimal('gross_amount', 18, 2);
            $table->decimal('fee', 18, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->json('metadata')->nullable(); // snap_token, redirect_url, raw response, dsb.
            $table->timestamps();

            $table->index(['status','channel']);
        });

        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('request_id', 64);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->integer('http_status')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['payment_id','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
        Schema::dropIfExists('payments');
    }
};
