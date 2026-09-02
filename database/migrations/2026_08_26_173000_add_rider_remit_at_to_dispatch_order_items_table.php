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

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('dispatch_order_items', 'rider_remit_at')) {
                $table->timestamp('rider_remit_at')->nullable()->after('admin_finished_at');
                $table->index('rider_remit_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dispatch_order_items') || ! Schema::hasColumn('dispatch_order_items', 'rider_remit_at')) {
            return;
        }

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            $table->dropIndex(['rider_remit_at']);
            $table->dropColumn('rider_remit_at');
        });
    }
};
