<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('from_user_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('to_user_id')->nullable()->after('from_user_id');

            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['from_user_id']);
            $table->dropForeign(['to_user_id']);
            $table->dropColumn(['from_user_id', 'to_user_id']);
        });
    }
};
