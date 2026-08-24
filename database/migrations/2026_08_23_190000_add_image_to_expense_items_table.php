<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public const DEMO_IMAGE = 'images/demo/expense-receipt.svg';

    public function up(): void
    {
        if (! Schema::hasTable('expense_items')) {
            return;
        }

        Schema::table('expense_items', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_items', 'image')) {
                $table->string('image', 500)->nullable()->after('amount');
            }
        });

        DB::table('expense_items')
            ->whereNull('image')
            ->where(function ($q) {
                $q->whereNull('source')->orWhere('source', '!=', 'rider_fuel');
            })
            ->update(['image' => self::DEMO_IMAGE]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('expense_items') || ! Schema::hasColumn('expense_items', 'image')) {
            return;
        }

        Schema::table('expense_items', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
