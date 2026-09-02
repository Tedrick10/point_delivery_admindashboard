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
            if (! Schema::hasColumn('dispatch_order_items', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('delivered_type');
                $table->index('delivered_at');
            }
        });

        // Backfill: treat last update as delivery time for already-completed parcels.
        if (Schema::hasColumn('dispatch_order_items', 'delivered_at')) {
            DB::table('dispatch_order_items')
                ->where('status', 'completed')
                ->whereNull('delivered_at')
                ->update([
                    'delivered_at' => DB::raw('COALESCE(updated_at, created_at)'),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('dispatch_order_items') || ! Schema::hasColumn('dispatch_order_items', 'delivered_at')) {
            return;
        }

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            $table->dropIndex(['delivered_at']);
            $table->dropColumn('delivered_at');
        });
    }
};
