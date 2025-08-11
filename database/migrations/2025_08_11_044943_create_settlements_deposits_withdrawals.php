<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('channel', 30)->nullable();
            $table->date('settlement_date');
            $table->integer('total_count')->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->string('file_url')->nullable();
            $table->string('status', 20)->default('open'); // open|reconciled|closed
            $table->timestamps();

            $table->unique(['provider','channel','settlement_date'],'settlements_unique_per_day');
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('method', 30); // va_bri, qris, manual_transfer
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending|paid|failed|expired|canceled
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id','created_at']);
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('bank_code', 10);
            $table->string('account_no', 64);   // simpan masked di app layer
            $table->string('account_name', 128);
            $table->string('status', 20)->default('requested'); // requested|processing|paid|failed
            $table->timestamp('processed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('deposits');
        Schema::dropIfExists('settlements');
    }
};
