<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hr_office_salary_rows')) {
            return;
        }

        Schema::table('hr_office_salary_rows', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_office_salary_rows', 'rest_off_dates')) {
                $table->json('rest_off_dates')->nullable()->after('rest_days');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hr_office_salary_rows')) {
            return;
        }

        Schema::table('hr_office_salary_rows', function (Blueprint $table) {
            if (Schema::hasColumn('hr_office_salary_rows', 'rest_off_dates')) {
                $table->dropColumn('rest_off_dates');
            }
        });
    }
};
