<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // USERS: tambah kolom dan index
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users','full_name')) $table->string('full_name')->nullable();
            if (!Schema::hasColumn('users','phone')) $table->string('phone')->nullable()->index();
            if (!Schema::hasColumn('users','kyc_status')) $table->string('kyc_status')->default('unverified'); // unverified|pending|verified|rejected
            if (!Schema::hasColumn('users','pin_hash')) $table->string('pin_hash')->nullable();
            if (!Schema::hasColumn('users','last_login_at')) $table->timestamp('last_login_at')->nullable();
            if (!Schema::hasColumn('users','is_active')) $table->boolean('is_active')->default(true)->index();
            if (!Schema::hasColumn('users','deleted_at')) $table->timestamp('deleted_at')->nullable()->index();
        });

        // ROLES / PERMISSIONS slug uniqueness - dengan pengecekan untuk PostgreSQL
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'roles_slug_unique'
            ) THEN
                ALTER TABLE roles ADD CONSTRAINT roles_slug_unique UNIQUE (slug);
            END IF;
        END$$;");
        
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'permissions_slug_unique'
            ) THEN
                ALTER TABLE permissions ADD CONSTRAINT permissions_slug_unique UNIQUE (slug);
            END IF;
        END$$;");

        // PIVOTS uniqueness - dengan pengecekan untuk PostgreSQL
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'role_permission_role_id_permission_id_uq'
            ) THEN
                ALTER TABLE role_permission ADD CONSTRAINT role_permission_role_id_permission_id_uq UNIQUE (role_id, permission_id);
            END IF;
        END$$;");
        
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'role_permission_role_id_foreign'
            ) THEN
                ALTER TABLE role_permission ADD CONSTRAINT role_permission_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE;
            END IF;
        END$$;");
        
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'role_permission_permission_id_foreign'
            ) THEN
                ALTER TABLE role_permission ADD CONSTRAINT role_permission_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE;
            END IF;
        END$$;");
        
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'user_role_user_id_role_id_uq'
            ) THEN
                ALTER TABLE user_role ADD CONSTRAINT user_role_user_id_role_id_uq UNIQUE (user_id, role_id);
            END IF;
        END$$;");
        
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'user_role_user_id_foreign'
            ) THEN
                ALTER TABLE user_role ADD CONSTRAINT user_role_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
            END IF;
        END$$;");
        
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'user_role_role_id_foreign'
            ) THEN
                ALTER TABLE user_role ADD CONSTRAINT user_role_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE;
            END IF;
        END$$;");

        // PRODUCTS: tambah kolom & constraints
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products','category')) $table->string('category')->nullable()->index(); // pulsa, data, pln_prepaid, dst.
            if (!Schema::hasColumn('products','provider_code')) $table->string('provider_code')->nullable()->index();
            if (!Schema::hasColumn('products','area_code')) $table->string('area_code')->nullable()->index();
            if (!Schema::hasColumn('products','min_amount')) $table->decimal('min_amount',18,2)->nullable();
            if (!Schema::hasColumn('products','max_amount')) $table->decimal('max_amount',18,2)->nullable();
        });
        
        // PRODUCTS constraints dengan pengecekan
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'products_code_unique'
            ) THEN
                ALTER TABLE products ADD CONSTRAINT products_code_unique UNIQUE (code);
            END IF;
        END$$;");
        
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_indexes WHERE indexname = 'products_provider_category_idx'
            ) THEN
                CREATE INDEX products_provider_category_idx ON products (provider, category);
            END IF;
        END$$;");
        
        // CHECK: price/admin_fee >= 0
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'products_price_nonneg'
            ) THEN
                ALTER TABLE products ADD CONSTRAINT products_price_nonneg CHECK (price >= 0);
            END IF;
        END$$;");
        
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'products_admin_fee_nonneg'
            ) THEN
                ALTER TABLE products ADD CONSTRAINT products_admin_fee_nonneg CHECK (admin_fee >= 0);
            END IF;
        END$$;");

        // TRANSACTIONS: tambah kolom & indexes
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions','external_id')) $table->string('external_id', 64)->nullable();
            if (!Schema::hasColumn('transactions','provider_trx_id')) $table->string('provider_trx_id', 64)->nullable();
            if (!Schema::hasColumn('transactions','provider_status')) $table->string('provider_status', 32)->nullable();
            if (!Schema::hasColumn('transactions','provider_code')) $table->string('provider_code', 64)->nullable();
            if (!Schema::hasColumn('transactions','channel')) $table->string('channel', 32)->nullable(); // midtrans_va, qris, ewallet, bank_transfer, cash_agent, dll
            if (!Schema::hasColumn('transactions','customer_ref')) $table->string('customer_ref', 64)->nullable(); // no meter/no pelanggan
            if (!Schema::hasColumn('transactions','request_payload')) $table->json('request_payload')->nullable();
            if (!Schema::hasColumn('transactions','callback_payload')) $table->json('callback_payload')->nullable();
            if (!Schema::hasColumn('transactions','expired_at')) $table->timestamp('expired_at')->nullable();
            if (!Schema::hasColumn('transactions','voided_at')) $table->timestamp('voided_at')->nullable();
        });

        // TRANSACTIONS constraints dengan pengecekan
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'transactions_transaction_id_unique'
            ) THEN
                ALTER TABLE transactions ADD CONSTRAINT transactions_transaction_id_unique UNIQUE (transaction_id);
            END IF;
        END$$;");
        
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'transactions_external_id_unique'
            ) THEN
                ALTER TABLE transactions ADD CONSTRAINT transactions_external_id_unique UNIQUE (external_id);
            END IF;
        END$$;");
        
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_indexes WHERE indexname = 'transactions_user_created_idx'
            ) THEN
                CREATE INDEX transactions_user_created_idx ON transactions (user_id, created_at);
            END IF;
        END$$;");
        
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_indexes WHERE indexname = 'transactions_product_id_idx'
            ) THEN
                CREATE INDEX transactions_product_id_idx ON transactions (product_id);
            END IF;
        END$$;");

        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'transactions_user_id_foreign'
            ) THEN
                ALTER TABLE transactions ADD CONSTRAINT transactions_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT;
            END IF;
        END$$;");
        
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'transactions_product_id_foreign'
            ) THEN
                ALTER TABLE transactions ADD CONSTRAINT transactions_product_id_foreign FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT;
            END IF;
        END$$;");

        // CHECK amount/fee
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'transactions_amount_nonneg'
            ) THEN
                ALTER TABLE transactions ADD CONSTRAINT transactions_amount_nonneg CHECK (amount >= 0);
            END IF;
        END$$;");
        
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'transactions_admin_fee_nonneg'
            ) THEN
                ALTER TABLE transactions ADD CONSTRAINT transactions_admin_fee_nonneg CHECK (admin_fee >= 0);
            END IF;
        END$$;");
        
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_constraint WHERE conname = 'transactions_total_amount_check'
            ) THEN
                ALTER TABLE transactions ADD CONSTRAINT transactions_total_amount_check CHECK (total_amount = amount + admin_fee);
            END IF;
        END$$;");
    }

    public function down(): void
    {
        // Rollback seperlunya (opsional lengkapkan jika ingin)
        // Hapus constraint by name sebelum drop cols
        foreach (['transactions_total_amount_check','transactions_admin_fee_nonneg','transactions_amount_nonneg'] as $c) {
            @DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS {$c}");
        }
        foreach (['products_admin_fee_nonneg','products_price_nonneg'] as $c) {
            @DB::statement("ALTER TABLE products DROP CONSTRAINT IF EXISTS {$c}");
        }
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_external_id_unique');
            $table->dropIndex('transactions_user_created_idx');
            $table->dropIndex('transactions_product_id_idx');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_code_unique');
            $table->dropIndex('products_provider_category_idx');
        });
        Schema::table('user_role', function (Blueprint $table) {
            $table->dropUnique('user_role_user_id_role_id_uq');
        });
        Schema::table('role_permission', function (Blueprint $table) {
            $table->dropUnique('role_permission_role_id_permission_id_uq');
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_slug_unique');
        });
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique('permissions_slug_unique');
        });
    }
};
