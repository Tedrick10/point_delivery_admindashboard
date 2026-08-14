<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('dispatch_order_items', 'admin_updated_at')) {
                $table->timestamp('admin_updated_at')->nullable()->after('assigned_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('dispatch_order_items', 'admin_updated_at')) {
                $table->dropColumn('admin_updated_at');
            }
        });
    }
};
