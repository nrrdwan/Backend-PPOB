<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check;");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check 
                    CHECK (type IN ('topup', 'pulsa', 'pln', 'pdam', 'game', 'emoney', 'other', 'withdraw'));");
    }

    public function down()
    {
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check;");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check 
                    CHECK (type IN ('topup', 'pulsa', 'pln', 'pdam', 'game', 'emoney', 'other'));");
    }
};
