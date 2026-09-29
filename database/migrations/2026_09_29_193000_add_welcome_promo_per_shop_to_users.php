<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'welcome_promo_enabled')) {
                $table->boolean('welcome_promo_enabled')->default(true)->after('welcome_orders_used');
            }
            if (! Schema::hasColumn('users', 'welcome_discount_percent')) {
                $table->decimal('welcome_discount_percent', 8, 2)->nullable()->after('welcome_promo_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'welcome_discount_percent')) {
                $table->dropColumn('welcome_discount_percent');
            }
            if (Schema::hasColumn('users', 'welcome_promo_enabled')) {
                $table->dropColumn('welcome_promo_enabled');
            }
        });
    }
};
