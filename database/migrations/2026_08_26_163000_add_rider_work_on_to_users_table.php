<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'rider_work_on')) {
                $table->boolean('rider_work_on')->default(true)->after('status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'rider_work_on')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rider_work_on');
        });
    }
};
