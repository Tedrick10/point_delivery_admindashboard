<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'is_shop_order')) {
                $table->tinyInteger('is_shop_order')->default(0)->after('is_text_order');
            }
            if (! Schema::hasColumn('orders', 'is_gate_order')) {
                $table->tinyInteger('is_gate_order')->default(0)->after('is_shop_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'is_gate_order')) {
                $table->dropColumn('is_gate_order');
            }
            if (Schema::hasColumn('orders', 'is_shop_order')) {
                $table->dropColumn('is_shop_order');
            }
        });
    }
};
