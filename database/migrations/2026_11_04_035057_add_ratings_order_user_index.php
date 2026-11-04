<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            if (! $this->indexExists('ratings', 'ratings_order_user_item_idx')) {
                $table->index(['order_id', 'user_id', 'dispatch_order_item_id'], 'ratings_order_user_item_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            if ($this->indexExists('ratings', 'ratings_order_user_item_idx')) {
                $table->dropIndex('ratings_order_user_item_idx');
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
