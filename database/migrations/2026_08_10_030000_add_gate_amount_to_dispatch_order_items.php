<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('dispatch_order_items', 'gate_amount')) {
                $table->double('gate_amount')->default(0)->after('os_to_pay');
            }
            if (! Schema::hasColumn('dispatch_order_items', 'gate_os_paid')) {
                $table->double('gate_os_paid')->default(0)->after('gate_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('dispatch_order_items', 'gate_os_paid')) {
                $table->dropColumn('gate_os_paid');
            }
            if (Schema::hasColumn('dispatch_order_items', 'gate_amount')) {
                $table->dropColumn('gate_amount');
            }
        });
    }
};
