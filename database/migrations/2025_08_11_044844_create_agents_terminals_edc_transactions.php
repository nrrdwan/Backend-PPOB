<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('terminals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('terminal_sn')->unique();
            $table->string('provider', 50); // bank/EDC brand
            $table->string('status', 20)->default('active');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('edc_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('terminal_id')->constrained('terminals')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('type', 32); // cash_out, transfer, balance_inquiry
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('rrn', 64)->nullable();
            $table->string('stan', 32)->nullable();
            $table->string('auth_code', 32)->nullable();
            $table->string('card_bin', 12)->nullable();
            $table->string('masked_pan', 32)->nullable();
            $table->string('response_code', 16)->nullable();
            $table->string('provider_status', 32)->nullable();
            $table->string('receipt_url')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['terminal_id','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edc_transactions');
        Schema::dropIfExists('terminals');
        Schema::dropIfExists('agents');
    }
};
