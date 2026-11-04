<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for Assign 100 pool, Order List filters, and admin live polls.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['status', 'delivery_man_id', 'hub_user_id', 'assigned_at'], 'dispatch_items_assign_pool_idx');
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['status', 'to_branch_id', 'delivery_man_id', 'assigned_at'], 'dispatch_items_assign_branch_idx');
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['order_id', 'status', 'admin_updated_at'], 'dispatch_items_admin_stamp_idx');
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['status', 'delivery_man_id', 'admin_updated_at'], 'dispatch_items_pool_admin_idx');
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['received_date', 'status'], 'dispatch_items_received_status_idx');
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['status', 'mdy_inbox_at', 'mdy_accepted_at', 'hub_user_id'], 'dispatch_items_mdy_inbox_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'orders', ['deleted_at', 'pickup_datetime', 'id'], 'orders_deleted_pickup_id_idx');
            $this->addIndexIfMissing($table, 'orders', ['deleted_at', 'created_at', 'id'], 'orders_deleted_created_id_idx');
            $this->addIndexIfMissing($table, 'orders', ['status', 'pickup_error_at', 'deleted_at'], 'orders_status_pickup_error_at_idx');
            $this->addIndexIfMissing($table, 'orders', ['deleted_at', 'status', 'delivery_man_id'], 'orders_deleted_status_rider_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'notifications', ['notifiable_type', 'notifiable_id', 'read_at', 'created_at'], 'notifications_notifiable_read_created_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'users', ['user_type', 'status', 'branch_id'], 'users_type_status_branch_idx');
            $this->addIndexIfMissing($table, 'users', ['hub_parent_id', 'user_type', 'status'], 'users_hub_parent_type_status_idx');
            $this->addIndexIfMissing($table, 'users', ['user_type', 'is_dispatch_hub', 'status'], 'users_type_dispatch_hub_status_idx');
        });

        Schema::table('order_histories', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'order_histories', ['created_at'], 'order_histories_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_order_items', function (Blueprint $table) {
            foreach ([
                'dispatch_items_assign_pool_idx',
                'dispatch_items_assign_branch_idx',
                'dispatch_items_admin_stamp_idx',
                'dispatch_items_pool_admin_idx',
                'dispatch_items_received_status_idx',
                'dispatch_items_mdy_inbox_idx',
            ] as $name) {
                $this->dropIndexIfExists($table, 'dispatch_order_items', $name);
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'orders_deleted_pickup_id_idx',
                'orders_deleted_created_id_idx',
                'orders_status_pickup_error_at_idx',
                'orders_deleted_status_rider_idx',
            ] as $name) {
                $this->dropIndexIfExists($table, 'orders', $name);
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'notifications', 'notifications_notifiable_read_created_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'users_type_status_branch_idx',
                'users_hub_parent_type_status_idx',
                'users_type_dispatch_hub_status_idx',
            ] as $name) {
                $this->dropIndexIfExists($table, 'users', $name);
            }
        });

        Schema::table('order_histories', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'order_histories', 'order_histories_created_at_idx');
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
