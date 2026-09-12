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
            if (! Schema::hasColumn('dispatch_order_items', 'point_amount')) {
                $table->double('point_amount')->default(0)->after('gate_os_paid');
            }
            if (! Schema::hasColumn('dispatch_order_items', 'agent_amount')) {
                $table->double('agent_amount')->default(0)->after('point_amount');
            }
            if (! Schema::hasColumn('dispatch_order_items', 'agent_expense_branch_id')) {
                $table->unsignedBigInteger('agent_expense_branch_id')->nullable()->after('agent_amount');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dispatch_order_items')) {
            return;
        }

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            foreach (['agent_expense_branch_id', 'agent_amount', 'point_amount'] as $col) {
                if (Schema::hasColumn('dispatch_order_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
