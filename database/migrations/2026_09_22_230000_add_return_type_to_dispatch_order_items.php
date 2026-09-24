<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dispatch_order_items')) {
            return;
        }

        if (! Schema::hasColumn('dispatch_order_items', 'return_type')) {
            Schema::table('dispatch_order_items', function (Blueprint $table) {
                $table->string('return_type', 16)->nullable()->after('assigned_from_return');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dispatch_order_items') && Schema::hasColumn('dispatch_order_items', 'return_type')) {
            Schema::table('dispatch_order_items', function (Blueprint $table) {
                $table->dropColumn('return_type');
            });
        }
    }
};
