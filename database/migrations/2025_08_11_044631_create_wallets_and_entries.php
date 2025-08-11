<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 18, 2)->default(0);
            $table->string('status', 20)->default('active'); // active|suspended
            $table->timestamps();
        });

        Schema::create('wallet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->string('entry_type', 10); // credit|debit
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_after', 18, 2);
            $table->string('ref_type', 30);   // transaction|deposit|withdraw|adjustment
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['wallet_id', 'created_at']);
            $table->index(['ref_type', 'ref_id']);
        });

        DB::statement("ALTER TABLE wallet_entries
            ADD CONSTRAINT wallet_entries_amount_pos CHECK (amount > 0)");
        DB::statement("ALTER TABLE wallet_entries
            ADD CONSTRAINT wallet_entries_entry_type_chk CHECK (entry_type IN ('credit','debit'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_entries');
        Schema::dropIfExists('wallets');
    }
};
