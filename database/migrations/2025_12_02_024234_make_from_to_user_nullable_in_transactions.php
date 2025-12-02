<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Make nullable
            $table->unsignedBigInteger('from_user_id')->nullable()->change();
            $table->unsignedBigInteger('to_user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Rollback ke NOT NULL (hati-hati, bisa error jika ada data NULL)
            $table->unsignedBigInteger('from_user_id')->nullable(false)->change();
            $table->unsignedBigInteger('to_user_id')->nullable(false)->change();
        });
    }
};