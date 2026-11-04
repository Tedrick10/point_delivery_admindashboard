<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for order-list / dispatch / rider / user hot paths.
 * Safe for local + production; uses hasIndex checks where available.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'orders', ['delivery_man_id', 'status', 'deleted_at'], 'orders_delivery_man_status_deleted_idx');
            $this->addIndexIfMissing($table, 'orders', ['client_id', 'status', 'deleted_at'], 'orders_client_status_deleted_idx');
            $this->addIndexIfMissing($table, 'orders', ['city_id', 'status', 'created_at'], 'orders_city_status_created_idx');
            $this->addIndexIfMissing($table, 'orders', ['status', 'deleted_at', 'id'], 'orders_status_deleted_id_idx');
            $this->addIndexIfMissing($table, 'orders', ['date'], 'orders_date_idx');
            $this->addIndexIfMissing($table, 'orders', ['pickup_datetime'], 'orders_pickup_datetime_idx');
            $this->addIndexIfMissing($table, 'orders', ['updated_at'], 'orders_updated_at_idx');
            $this->addIndexIfMissing($table, 'orders', ['payment_id'], 'orders_payment_id_idx');
        });

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['delivery_man_id', 'status', 'deleted_at'], 'dispatch_items_rider_status_deleted_idx');
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['status', 'deleted_at'], 'dispatch_items_status_deleted_idx');
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['code'], 'dispatch_items_code_idx');
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['city_id', 'status'], 'dispatch_items_city_status_idx');
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['from_branch_id', 'status'], 'dispatch_items_from_branch_status_idx');
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['to_branch_id', 'status'], 'dispatch_items_to_branch_status_idx');
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['updated_at'], 'dispatch_items_updated_at_idx');
        });

        Schema::table('profofpictures', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'profofpictures', ['order_id', 'type'], 'profofpictures_order_type_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'users', ['user_type', 'status', 'deleted_at'], 'users_type_status_deleted_idx');
            $this->addIndexIfMissing($table, 'users', ['contact_number'], 'users_contact_number_idx');
            $this->addIndexIfMissing($table, 'users', ['city_id', 'user_type'], 'users_city_type_idx');
        });

        Schema::table('order_histories', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'order_histories', ['order_id', 'created_at'], 'order_histories_order_created_idx');
            $this->addIndexIfMissing($table, 'order_histories', ['updated_at'], 'order_histories_updated_at_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'notifications', ['created_at'], 'notifications_created_at_idx');
            $this->addIndexIfMissing($table, 'notifications', ['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'orders', 'orders_delivery_man_status_deleted_idx');
            $this->dropIndexIfExists($table, 'orders', 'orders_client_status_deleted_idx');
            $this->dropIndexIfExists($table, 'orders', 'orders_city_status_created_idx');
            $this->dropIndexIfExists($table, 'orders', 'orders_status_deleted_id_idx');
            $this->dropIndexIfExists($table, 'orders', 'orders_date_idx');
            $this->dropIndexIfExists($table, 'orders', 'orders_pickup_datetime_idx');
            $this->dropIndexIfExists($table, 'orders', 'orders_updated_at_idx');
            $this->dropIndexIfExists($table, 'orders', 'orders_payment_id_idx');
        });

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'dispatch_order_items', 'dispatch_items_rider_status_deleted_idx');
            $this->dropIndexIfExists($table, 'dispatch_order_items', 'dispatch_items_status_deleted_idx');
            $this->dropIndexIfExists($table, 'dispatch_order_items', 'dispatch_items_code_idx');
            $this->dropIndexIfExists($table, 'dispatch_order_items', 'dispatch_items_city_status_idx');
            $this->dropIndexIfExists($table, 'dispatch_order_items', 'dispatch_items_from_branch_status_idx');
            $this->dropIndexIfExists($table, 'dispatch_order_items', 'dispatch_items_to_branch_status_idx');
            $this->dropIndexIfExists($table, 'dispatch_order_items', 'dispatch_items_updated_at_idx');
        });

        Schema::table('profofpictures', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'profofpictures', 'profofpictures_order_type_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'users', 'users_type_status_deleted_idx');
            $this->dropIndexIfExists($table, 'users', 'users_contact_number_idx');
            $this->dropIndexIfExists($table, 'users', 'users_city_type_idx');
        });

        Schema::table('order_histories', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'order_histories', 'order_histories_order_created_idx');
            $this->dropIndexIfExists($table, 'order_histories', 'order_histories_updated_at_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'notifications', 'notifications_created_at_idx');
            $this->dropIndexIfExists($table, 'notifications', 'notifications_notifiable_read_idx');
        });
    }

    private function addIndexIfMissing(Blueprint $table, string $tableName, array $columns, string $indexName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            return;
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
