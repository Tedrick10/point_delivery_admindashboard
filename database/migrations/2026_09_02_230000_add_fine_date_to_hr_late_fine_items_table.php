<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_late_fine_items', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_late_fine_items', 'fine_date')) {
                $table->date('fine_date')->nullable()->after('staff_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_late_fine_items', function (Blueprint $table) {
            if (Schema::hasColumn('hr_late_fine_items', 'fine_date')) {
                $table->dropColumn('fine_date');
            }
        });
    }
};
