<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_type_id')->nullable()->after('status');
            $table->foreign('employee_type_id')
                ->references('id')
                ->on('employee_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['employee_type_id']);
            $table->dropColumn('employee_type_id');
        });
    }
};
