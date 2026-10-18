<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dispatch_order_items')) {
            return;
        }

        if (Schema::hasColumn('dispatch_order_items', 'on_way_at')
            && ! Schema::hasColumn('dispatch_order_items', 'status_ordered_at')) {
            // Avoid doctrine/dbal renameColumn requirement.
            Schema::table('dispatch_order_items', function (Blueprint $table) {
                $table->timestamp('status_ordered_at')->nullable()->after('assigned_at')->index();
            });
            DB::table('dispatch_order_items')->update([
                'status_ordered_at' => DB::raw('on_way_at'),
            ]);
            Schema::table('dispatch_order_items', function (Blueprint $table) {
                $table->dropColumn('on_way_at');
            });
        } elseif (! Schema::hasColumn('dispatch_order_items', 'status_ordered_at')) {
            Schema::table('dispatch_order_items', function (Blueprint $table) {
                $table->timestamp('status_ordered_at')->nullable()->after('assigned_at')->index();
            });
        }

        if (Schema::hasColumn('dispatch_order_items', 'status_ordered_at')) {
            DB::table('dispatch_order_items')
                ->whereNull('status_ordered_at')
                ->whereNull('deleted_at')
                ->update([
                    'status_ordered_at' => DB::raw('COALESCE(assigned_at, updated_at, created_at, NOW())'),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('dispatch_order_items')) {
            return;
        }

        if (Schema::hasColumn('dispatch_order_items', 'status_ordered_at')
            && ! Schema::hasColumn('dispatch_order_items', 'on_way_at')) {
            Schema::table('dispatch_order_items', function (Blueprint $table) {
                $table->timestamp('on_way_at')->nullable()->after('assigned_at')->index();
            });
            DB::table('dispatch_order_items')->update([
                'on_way_at' => DB::raw('status_ordered_at'),
            ]);
            Schema::table('dispatch_order_items', function (Blueprint $table) {
                $table->dropColumn('status_ordered_at');
            });
        }
    }
};
