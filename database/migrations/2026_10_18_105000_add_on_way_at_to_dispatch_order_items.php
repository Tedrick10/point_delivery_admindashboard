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

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('dispatch_order_items', 'on_way_at')) {
                $table->timestamp('on_way_at')->nullable()->after('assigned_at')->index();
            }
        });

        // Existing On Way rows: keep relative order by updated_at.
        if (Schema::hasColumn('dispatch_order_items', 'on_way_at')) {
            DB::table('dispatch_order_items')
                ->where('status', 'courier_departed')
                ->whereNull('on_way_at')
                ->whereNull('deleted_at')
                ->orderBy('updated_at')
                ->orderBy('id')
                ->update([
                    'on_way_at' => DB::raw('COALESCE(updated_at, created_at, NOW())'),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('dispatch_order_items')) {
            return;
        }

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('dispatch_order_items', 'on_way_at')) {
                $table->dropColumn('on_way_at');
            }
        });
    }
};
