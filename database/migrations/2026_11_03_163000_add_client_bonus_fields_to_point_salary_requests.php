<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('point_salary_requests')) {
            return;
        }

        Schema::table('point_salary_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('point_salary_requests', 'request_kind')) {
                $table->string('request_kind', 32)->default('staff')->after('user_id')->index();
            }
            if (! Schema::hasColumn('point_salary_requests', 'points_held')) {
                $table->boolean('points_held')->default(true)->after('amount');
            }
            if (! Schema::hasColumn('point_salary_requests', 'os_pay_settled_amount')) {
                $table->decimal('os_pay_settled_amount', 14, 2)->default(0)->after('points_held');
            }
            if (! Schema::hasColumn('point_salary_requests', 'os_pay_settled_item_ids')) {
                $table->json('os_pay_settled_item_ids')->nullable()->after('os_pay_settled_amount');
            }
        });

        if (! Schema::hasColumn('dispatch_order_items', 'os_pay_point_note')) {
            Schema::table('dispatch_order_items', function (Blueprint $table) {
                $table->string('os_pay_point_note', 255)->nullable()->after('os_paid');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('point_salary_requests')) {
            Schema::table('point_salary_requests', function (Blueprint $table) {
                foreach (['request_kind', 'points_held', 'os_pay_settled_amount', 'os_pay_settled_item_ids'] as $col) {
                    if (Schema::hasColumn('point_salary_requests', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('dispatch_order_items') && Schema::hasColumn('dispatch_order_items', 'os_pay_point_note')) {
            Schema::table('dispatch_order_items', function (Blueprint $table) {
                $table->dropColumn('os_pay_point_note');
            });
        }
    }
};
