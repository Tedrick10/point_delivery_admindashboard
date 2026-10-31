<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hr_office_salary_rows')
            && ! Schema::hasColumn('hr_office_salary_rows', 'point_salary_amount')) {
            Schema::table('hr_office_salary_rows', function (Blueprint $table) {
                $table->decimal('point_salary_amount', 12, 2)->default(0)->after('deposit');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hr_office_salary_rows')
            && Schema::hasColumn('hr_office_salary_rows', 'point_salary_amount')) {
            Schema::table('hr_office_salary_rows', function (Blueprint $table) {
                $table->dropColumn('point_salary_amount');
            });
        }
    }
};
