<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Untuk PostgreSQL
        DB::statement("
            ALTER TABLE transactions 
            DROP CONSTRAINT IF EXISTS transactions_type_check
        ");
        
        DB::statement("
            ALTER TABLE transactions 
            ADD CONSTRAINT transactions_type_check 
            CHECK (type IN (
                'topup', 
                'withdraw', 
                'purchase', 
                'pulsa', 
                'data', 
                'pln', 
                'pdam', 
                'game', 
                'emoney', 
                'other',
                'qr_transfer',
                'qr_receive'
            ))
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE transactions 
            DROP CONSTRAINT IF EXISTS transactions_type_check
        ");
        
        // Restore constraint tanpa qr_transfer dan qr_receive
        DB::statement("
            ALTER TABLE transactions 
            ADD CONSTRAINT transactions_type_check 
            CHECK (type IN (
                'topup', 
                'withdraw', 
                'purchase', 
                'pulsa', 
                'data', 
                'pln', 
                'pdam', 
                'game', 
                'emoney', 
                'other'
            ))
        ");
    }
};