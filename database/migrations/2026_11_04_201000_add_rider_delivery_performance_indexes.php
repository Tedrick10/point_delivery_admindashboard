<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for Rider Delivery / Rider List / Daily Tracking hot paths.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            $this->addIndexIfMissing(
                $table,
                'dispatch_order_items',
                ['delivery_man_id', 'status', 'admin_completed_at', 'status_ordered_at', 'id'],
                'dispatch_items_rider_delivery_list_idx'
            );
            $this->addIndexIfMissing(
                $table,
                'dispatch_order_items',
                ['delivery_man_id', 'status', 'admin_finished_at', 'id'],
                'dispatch_items_rider_finished_idx'
            );
            $this->addIndexIfMissing(
                $table,
                'dispatch_order_items',
                ['delivery_man_id', 'status', 'assigned_at', 'id'],
                'dispatch_items_rider_assigned_idx'
            );
            $this->addIndexIfMissing(
                $table,
                'dispatch_order_items',
                ['delivery_man_id', 'status', 'rider_remit_date', 'admin_completed_at', 'id'],
                'dispatch_items_rider_remit_list_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            foreach ([
                'dispatch_items_rider_delivery_list_idx',
                'dispatch_items_rider_finished_idx',
                'dispatch_items_rider_assigned_idx',
                'dispatch_items_rider_remit_list_idx',
            ] as $name) {
                $this->dropIndexIfExists($table, 'dispatch_order_items', $name);
            }
        });
    }

    private function addIndexIfMissing(Blueprint $table, string $tableName, array $columns, string $indexName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($tableName, $column)) {
                return;
            }
        }

        $table->index($columns, $indexName);
    }

    private function dropIndexIfExists(Blueprint $table, string $tableName, string $indexName): void
    {
        if (! $this->indexExists($tableName, $indexName)) {
            return;
        }

        $table->dropIndex($indexName);
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
