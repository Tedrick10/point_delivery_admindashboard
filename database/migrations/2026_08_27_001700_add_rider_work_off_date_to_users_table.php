<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'rider_work_off_date')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->date('rider_work_off_date')->nullable()->after('rider_work_on');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'rider_work_off_date')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rider_work_off_date');
        });
    }
};
