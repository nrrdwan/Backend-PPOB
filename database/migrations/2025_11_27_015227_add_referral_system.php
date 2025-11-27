<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tambah kolom referral_code ke tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 6)->unique()->nullable()->after('fcm_token');
            $table->string('referred_by', 6)->nullable()->after('referral_code');
            $table->integer('referral_count')->default(0)->after('referred_by');
            $table->decimal('referral_earnings', 15, 2)->default(0)->after('referral_count');
        });

        // Buat tabel referral_transactions untuk tracking
        Schema::create('referral_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade');
            $table->string('referral_code', 6);
            $table->integer('referral_number'); // Urutan referral ke berapa
            $table->decimal('commission_amount', 15, 2);
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('paid');
            $table->timestamps();

            $table->index('referrer_id');
            $table->index('referred_id');
            $table->index('referral_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_transactions');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['referral_code', 'referred_by', 'referral_count', 'referral_earnings']);
        });
    }
};