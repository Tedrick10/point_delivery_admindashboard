<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            // Matches Assign 100 branch tab: status + to_branch_id, sorted by assigned_at/id.
            if (! $this->indexExists('dispatch_order_items', 'dispatch_items_assign_branch_sort_idx')
                && Schema::hasColumn('dispatch_order_items', 'to_branch_id')
                && Schema::hasColumn('dispatch_order_items', 'assigned_at')) {
                $table->index(['status', 'to_branch_id', 'assigned_at', 'id'], 'dispatch_items_assign_branch_sort_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            if ($this->indexExists('dispatch_order_items', 'dispatch_items_assign_branch_sort_idx')) {
                $table->dropIndex('dispatch_items_assign_branch_sort_idx');
            }
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        try {
            $db = Schema::getConnection()->getDatabaseName();
            $row = Schema::getConnection()->selectOne(
                'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                [$db, $tableName, $indexName]
            );

            return ((int) ($row->c ?? 0)) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
