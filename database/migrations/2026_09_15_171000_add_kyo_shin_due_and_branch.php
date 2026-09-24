<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kyo_shin_items')) {
            return;
        }

        Schema::table('kyo_shin_items', function (Blueprint $table) {
            if (! Schema::hasColumn('kyo_shin_items', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('scope_key');
            }
            if (! Schema::hasColumn('kyo_shin_items', 'due_finished_at')) {
                $table->date('due_finished_at')->nullable()->after('advanced_paid_by');
            }
            if (! Schema::hasColumn('kyo_shin_items', 'last_overdue_notified_on')) {
                $table->date('last_overdue_notified_on')->nullable()->after('finished_by');
            }
        });

        if (Schema::hasColumn('kyo_shin_items', 'branch_id')) {
            Schema::table('kyo_shin_items', function (Blueprint $table) {
                $table->index(['branch_id', 'status']);
                $table->index(['due_finished_at', 'status']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('kyo_shin_items')) {
            return;
        }

        Schema::table('kyo_shin_items', function (Blueprint $table) {
            if (Schema::hasColumn('kyo_shin_items', 'branch_id')) {
                $table->dropIndex(['branch_id', 'status']);
            }
            if (Schema::hasColumn('kyo_shin_items', 'due_finished_at')) {
                $table->dropIndex(['due_finished_at', 'status']);
            }
            foreach (['branch_id', 'due_finished_at', 'last_overdue_notified_on'] as $column) {
                if (Schema::hasColumn('kyo_shin_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
