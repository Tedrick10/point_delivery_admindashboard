<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_staff', function (Blueprint $table) {
            $table->decimal('way_rate', 12, 2)->default(1000)->after('allowance_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('hr_staff', function (Blueprint $table) {
            $table->dropColumn('way_rate');
        });
    }
};
