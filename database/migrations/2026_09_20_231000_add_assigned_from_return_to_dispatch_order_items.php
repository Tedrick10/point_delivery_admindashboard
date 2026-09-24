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
            if (! Schema::hasColumn('dispatch_order_items', 'assigned_from_return')) {
                $table->boolean('assigned_from_return')->default(false)->after('return_reassigned');
            }
        });

        if (Schema::hasColumn('dispatch_order_items', 'return_reassigned')
            && Schema::hasColumn('dispatch_order_items', 'assigned_from_return')) {
            DB::table('dispatch_order_items')
                ->where('return_reassigned', true)
                ->update(['assigned_from_return' => true]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('dispatch_order_items')) {
            return;
        }

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('dispatch_order_items', 'assigned_from_return')) {
                $table->dropColumn('assigned_from_return');
            }
        });
    }
};
