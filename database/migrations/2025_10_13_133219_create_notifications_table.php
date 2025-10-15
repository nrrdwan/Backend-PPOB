<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 🔹 Pastikan ENUM type sudah ada (PostgreSQL only)
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'notification_type') THEN
                    CREATE TYPE notification_type AS ENUM (
                        'deposit_success',
                        'deposit_failed',
                        'payment',
                        'system',
                        'promo',
                        'info'
                    );
                END IF;
            END$$;
        ");

        Schema::create('notifications', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('title', 255);
        $table->text('message');
        
        // 🔹 Simpan sebagai VARCHAR, tapi tambahkan constraint manual ENUM PostgreSQL
        $table->string('type', 50)->default('info');
        $table->boolean('is_read')->default(false);
        $table->timestampsTz();
    });

        // 🔹 Tambahkan index untuk optimasi
        DB::statement("
            ALTER TABLE notifications
            ADD CONSTRAINT notifications_type_check
            CHECK (type IN ('deposit_success', 'deposit_failed', 'payment', 'system', 'promo', 'info'));
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        DB::statement('DROP TYPE IF EXISTS notification_type');
    }
};