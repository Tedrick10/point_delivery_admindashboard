<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * cPanel MySQL often creates tables as latin1. Myanmar text in
     * orders.parcel_type (e.g. "စာဖြင့် အော်ဒါ") then fails with:
     * SQLSTATE[22007]: Incorrect string value ... for column parcel_type
     */
    public function up(): void
    {
        $tables = [
            'orders',
            'dispatch_order_items',
            'dispatch_item_messages',
            'dispatch_item_pending_remarks',
            'order_histories',
            'order_chat_messages',
            'users',
            'user_addresses',
            'notifications',
            'customer_supports',
            'claims',
            'claims_histories',
            'ratings',
            'expense_items',
            'expense_cards',
        ];

        $db = DB::getDatabaseName();
        DB::statement("ALTER DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            DB::statement("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }

    public function down(): void
    {
        // Charset upgrade is not reversed.
    }
};
