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
            if (! Schema::hasColumn('point_salary_requests', 'os_pay_settled_details')) {
                $table->json('os_pay_settled_details')->nullable()->after('os_pay_settled_item_ids');
            }
            if (! Schema::hasColumn('point_salary_requests', 'points_remaining')) {
                $table->decimal('points_remaining', 14, 2)->nullable()->after('os_pay_settled_details');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('point_salary_requests')) {
            return;
        }

        Schema::table('point_salary_requests', function (Blueprint $table) {
            foreach (['os_pay_settled_details', 'points_remaining'] as $col) {
                if (Schema::hasColumn('point_salary_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
