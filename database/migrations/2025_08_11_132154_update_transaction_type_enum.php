<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus constraint lama (nama constraint bisa dicek di error)
        DB::statement('ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check');

        // Tambahkan constraint baru dengan nilai withdraw
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check CHECK (type IN ('pulsa', 'data', 'pln', 'pdam', 'game', 'emoney', 'other', 'withdraw'))");
    }

    public function down(): void
    {
        // Rollback ke constraint lama (tanpa withdraw)
        DB::statement('ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check');
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check CHECK (type IN ('pulsa', 'data', 'pln', 'pdam', 'game', 'emoney', 'other'))");
    }
};