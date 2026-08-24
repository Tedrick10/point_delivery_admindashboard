<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expense_items')) {
            return;
        }

        Schema::table('expense_items', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_items', 'image_path')) {
                $table->string('image_path', 500)->nullable()->after('amount');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expense_items') || ! Schema::hasColumn('expense_items', 'image_path')) {
            return;
        }

        Schema::table('expense_items', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
