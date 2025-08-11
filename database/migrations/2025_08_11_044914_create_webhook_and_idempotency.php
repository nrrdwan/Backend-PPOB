<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('source', 40); // midtrans, provider_x
            $table->string('event_type', 64)->nullable();
            $table->string('external_id', 64)->nullable(); // order_id dsb.
            $table->json('payload');
            $table->string('signature', 255)->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->string('status', 20)->default('pending'); // pending|processed|failed

            $table->unique('external_id');
            $table->index(['status','received_at']);
        });

        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('scope', 30); // payment|inquiry|purchase
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();

            $table->index(['scope','expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('webhook_events');
    }
};
