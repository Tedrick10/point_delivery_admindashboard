<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DISPATCH_COLUMNS = [
        'is_city_to_city',
        'origin_branch_id',
        'destination_branch_id',
        'current_branch_id',
        'transfer_rider_id',
        'assignment_stage',
        'transfer_at',
        'destination_arrived_at',
        'transport_fee',
    ];

    public function up(): void
    {
        DB::table('dispatch_order_items')
            ->whereIn('status', ['sent_to_bus_gate', 'pre_pickup', 'gate_pickup', 'assigned_200'])
            ->update([
                'status' => 'assigned',
                'delivery_man_id' => null,
            ]);

        Schema::dropIfExists('dispatch_item_branch_shares');
        Schema::dropIfExists('branch_share_settings');

        foreach (self::DISPATCH_COLUMNS as $column) {
            if (Schema::hasColumn('dispatch_order_items', $column)) {
                Schema::table('dispatch_order_items', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        if (Schema::hasColumn('branches', 'is_hub')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('is_hub');
            });
        }

        DB::table('users')
            ->where('id', 1)
            ->where('user_type', 'admin')
            ->update(['branch_id' => null]);
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'is_hub')) {
                $table->boolean('is_hub')->default(false)->after('phone');
            }
        });

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('dispatch_order_items', 'is_city_to_city')) {
                $table->boolean('is_city_to_city')->default(false)->after('to_branch_id');
                $table->unsignedBigInteger('origin_branch_id')->nullable()->after('is_city_to_city');
                $table->unsignedBigInteger('destination_branch_id')->nullable()->after('origin_branch_id');
                $table->unsignedBigInteger('current_branch_id')->nullable()->after('destination_branch_id');
                $table->unsignedBigInteger('transfer_rider_id')->nullable()->after('delivery_man_id');
                $table->string('assignment_stage', 32)->default('local')->after('transfer_rider_id');
                $table->timestamp('transfer_at')->nullable()->after('assigned_at');
                $table->timestamp('destination_arrived_at')->nullable()->after('transfer_at');
                $table->decimal('transport_fee', 12, 2)->default(0)->after('gate_os_paid');
            }
        });

        Schema::create('branch_share_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('origin_fee_percent', 5, 2)->default(50);
            $table->decimal('destination_fee_percent', 5, 2)->default(50);
            $table->decimal('origin_transport_percent', 5, 2)->default(50);
            $table->decimal('destination_transport_percent', 5, 2)->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dispatch_item_branch_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dispatch_order_item_id');
            $table->unsignedBigInteger('origin_branch_id')->nullable();
            $table->unsignedBigInteger('destination_branch_id')->nullable();
            $table->decimal('total_delivery_fee', 12, 2)->default(0);
            $table->decimal('origin_fee_share', 12, 2)->default(0);
            $table->decimal('destination_fee_share', 12, 2)->default(0);
            $table->decimal('total_transport_fee', 12, 2)->default(0);
            $table->decimal('origin_transport_cost', 12, 2)->default(0);
            $table->decimal('destination_transport_cost', 12, 2)->default(0);
            $table->decimal('net_origin', 12, 2)->default(0);
            $table->decimal('net_destination', 12, 2)->default(0);
            $table->string('settlement_status', 32)->default('pending');
            $table->date('settlement_date')->nullable();
            $table->timestamps();
        });
    }
};
