<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'orders', ['milisecond'], 'orders_milisecond_idx');
            $this->addIndexIfMissing($table, 'orders', ['status', 'pickup_error_choice', 'deleted_at'], 'orders_status_pickup_error_idx');
            $this->addIndexIfMissing($table, 'orders', ['is_photo_order', 'status', 'deleted_at'], 'orders_photo_status_deleted_idx');
            $this->addIndexIfMissing($table, 'orders', ['is_text_order', 'status', 'deleted_at'], 'orders_text_status_deleted_idx');
            $this->addIndexIfMissing($table, 'orders', ['pickup_datetime', 'status', 'deleted_at'], 'orders_pickup_status_deleted_idx');
        });

        Schema::table('dispatch_item_messages', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'dispatch_item_messages', ['sender_type', 'created_at'], 'dispatch_msgs_sender_created_idx');
            $this->addIndexIfMissing($table, 'dispatch_item_messages', ['dispatch_order_item_id', 'id'], 'dispatch_msgs_item_id_idx');
        });

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['order_id', 'status', 'deleted_at'], 'dispatch_items_order_status_deleted_idx');
            $this->addIndexIfMissing($table, 'dispatch_order_items', ['admin_completed_at'], 'dispatch_items_admin_completed_idx');
        });

        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $this->addIndexIfMissing($table, 'personal_access_tokens', ['last_used_at'], 'pat_last_used_at_idx');
                $this->addIndexIfMissing($table, 'personal_access_tokens', ['expires_at'], 'pat_expires_at_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'orders_milisecond_idx',
                'orders_status_pickup_error_idx',
                'orders_photo_status_deleted_idx',
                'orders_text_status_deleted_idx',
                'orders_pickup_status_deleted_idx',
            ] as $name) {
                $this->dropIndexIfExists($table, 'orders', $name);
            }
        });

        Schema::table('dispatch_item_messages', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'dispatch_item_messages', 'dispatch_msgs_sender_created_idx');
            $this->dropIndexIfExists($table, 'dispatch_item_messages', 'dispatch_msgs_item_id_idx');
        });

        Schema::table('dispatch_order_items', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'dispatch_order_items', 'dispatch_items_order_status_deleted_idx');
            $this->dropIndexIfExists($table, 'dispatch_order_items', 'dispatch_items_admin_completed_idx');
        });

        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'personal_access_tokens', 'pat_last_used_at_idx');
                $this->dropIndexIfExists($table, 'personal_access_tokens', 'pat_expires_at_idx');
            });
        }
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
