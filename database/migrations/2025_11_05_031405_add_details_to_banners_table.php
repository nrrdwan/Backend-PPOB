<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->string('promo_code', 50)->nullable()->after('description');
            $table->timestamp('valid_until')->nullable()->after('promo_code');
            $table->text('terms_conditions')->nullable()->after('valid_until');
        });
    }

    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['description', 'promo_code', 'valid_until', 'terms_conditions']);
        });
    }
};