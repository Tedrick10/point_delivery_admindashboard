<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('point_order_payments')) {
            return;
        }

        Schema::table('point_order_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('point_order_payments', 'os_receive_settlement_id')) {
                $table->unsignedBigInteger('os_receive_settlement_id')->nullable()->index()->after('order_id');
            }
            if (! Schema::hasColumn('point_order_payments', 'payment_kind')) {
                $table->string('payment_kind', 32)->default('order')->index()->after('os_receive_settlement_id');
            }
        });

        // Allow OS-receive point payments without a delivery order.
        Schema::table('point_order_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('point_order_payments')) {
            return;
        }

        Schema::table('point_order_payments', function (Blueprint $table) {
            if (Schema::hasColumn('point_order_payments', 'payment_kind')) {
                $table->dropColumn('payment_kind');
            }
            if (Schema::hasColumn('point_order_payments', 'os_receive_settlement_id')) {
                $table->dropColumn('os_receive_settlement_id');
            }
        });
    }
};
