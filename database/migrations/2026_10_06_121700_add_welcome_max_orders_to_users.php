<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'welcome_max_orders')) {
                $table->unsignedSmallInteger('welcome_max_orders')->nullable()->after('welcome_discount_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'welcome_max_orders')) {
                $table->dropColumn('welcome_max_orders');
            }
        });
    }
};
