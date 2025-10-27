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
        // 1️⃣ Hapus constraint lama (kalau ada)
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

        // 2️⃣ Ubah kolom role ke string
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
        });

        // 3️⃣ Tambahkan constraint baru (opsional)
        DB::statement("
            ALTER TABLE users 
            ADD CONSTRAINT users_role_check 
            CHECK (role IN ('admin', 'agen', 'user', 'operator'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus constraint baru
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

        // Ubah kembali ke enum
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'agen', 'user', 'operator'])
                  ->default('user')
                  ->change();
        });

        // Tambahkan kembali constraint
        DB::statement("
            ALTER TABLE users 
            ADD CONSTRAINT users_role_check 
            CHECK (role IN ('admin', 'agen', 'user', 'operator'))
        ");
    }
};