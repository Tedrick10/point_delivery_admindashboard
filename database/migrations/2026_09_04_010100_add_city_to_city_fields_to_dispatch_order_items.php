<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('dispatch_order_items', 'is_city_to_city')) {
                $table->boolean('is_city_to_city')->default(false)->after('to_branch_id');
            }
            if (! Schema::hasColumn('dispatch_order_items', 'origin_branch_id')) {
                $table->unsignedBigInteger('origin_branch_id')->nullable()->after('is_city_to_city');
            }
            if (! Schema::hasColumn('dispatch_order_items', 'destination_branch_id')) {
                $table->unsignedBigInteger('destination_branch_id')->nullable()->after('origin_branch_id');
            }
            if (! Schema::hasColumn('dispatch_order_items', 'current_branch_id')) {
                $table->unsignedBigInteger('current_branch_id')->nullable()->after('destination_branch_id');
            }
            if (! Schema::hasColumn('dispatch_order_items', 'transfer_rider_id')) {
                $table->unsignedBigInteger('transfer_rider_id')->nullable()->after('delivery_man_id');
            }
            if (! Schema::hasColumn('dispatch_order_items', 'assignment_stage')) {
                $table->string('assignment_stage', 32)->default('local')->after('transfer_rider_id');
            }
            if (! Schema::hasColumn('dispatch_order_items', 'transfer_at')) {
                $table->timestamp('transfer_at')->nullable()->after('assigned_at');
            }
            if (! Schema::hasColumn('dispatch_order_items', 'destination_arrived_at')) {
                $table->timestamp('destination_arrived_at')->nullable()->after('transfer_at');
            }
            if (! Schema::hasColumn('dispatch_order_items', 'transport_fee')) {
                $table->decimal('transport_fee', 12, 2)->default(0)->after('gate_os_paid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            foreach ([
                'is_city_to_city',
                'origin_branch_id',
                'destination_branch_id',
                'current_branch_id',
                'transfer_rider_id',
                'assignment_stage',
                'transfer_at',
                'destination_arrived_at',
                'transport_fee',
            ] as $col) {
                if (Schema::hasColumn('dispatch_order_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
