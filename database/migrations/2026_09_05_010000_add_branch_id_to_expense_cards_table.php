<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expense_cards')) {
            return;
        }

        Schema::table('expense_cards', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_cards', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('expense_date')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expense_cards')) {
            return;
        }

        Schema::table('expense_cards', function (Blueprint $table) {
            if (Schema::hasColumn('expense_cards', 'branch_id')) {
                $table->dropColumn('branch_id');
            }
        });
    }
};
