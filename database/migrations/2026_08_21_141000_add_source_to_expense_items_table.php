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
            if (! Schema::hasColumn('expense_items', 'source')) {
                $table->string('source', 40)->nullable()->after('sort_order');
                $table->index(['expense_card_id', 'source']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expense_items') || ! Schema::hasColumn('expense_items', 'source')) {
            return;
        }

        Schema::table('expense_items', function (Blueprint $table) {
            $table->dropIndex(['expense_card_id', 'source']);
            $table->dropColumn('source');
        });
    }
};
