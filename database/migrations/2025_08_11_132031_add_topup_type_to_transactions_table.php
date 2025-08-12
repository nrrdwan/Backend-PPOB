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
        // For PostgreSQL, we need to add the new enum value
        DB::statement("ALTER TYPE transaction_type ADD VALUE IF NOT EXISTS 'topup'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // PostgreSQL doesn't support removing enum values easily
        // So we'll leave it as is
    }
};
