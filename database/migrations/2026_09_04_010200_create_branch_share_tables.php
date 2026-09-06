<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branch_share_settings')) {
            Schema::create('branch_share_settings', function (Blueprint $table) {
                $table->id();
                $table->decimal('origin_fee_percent', 5, 2)->default(50);
                $table->decimal('destination_fee_percent', 5, 2)->default(50);
                $table->decimal('origin_transport_percent', 5, 2)->default(50);
                $table->decimal('destination_transport_percent', 5, 2)->default(50);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            DB::table('branch_share_settings')->insert([
                'origin_fee_percent' => 50,
                'destination_fee_percent' => 50,
                'origin_transport_percent' => 50,
                'destination_transport_percent' => 50,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('dispatch_item_branch_shares')) {
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

                $table->index('dispatch_order_item_id', 'dibs_item_idx');
                $table->index('settlement_status', 'dibs_status_idx');
                $table->index('settlement_date', 'dibs_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_item_branch_shares');
        Schema::dropIfExists('branch_share_settings');
    }
};
