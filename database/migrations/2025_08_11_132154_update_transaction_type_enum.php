<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, we need to alter the column to allow 'topup' value
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check CHECK (type IN ('pulsa', 'data', 'pln', 'pdam', 'game', 'emoney', 'other', 'topup'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original constraint
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check CHECK (type IN ('pulsa', 'data', 'pln', 'pdam', 'game', 'emoney', 'other'))");
    }
};
