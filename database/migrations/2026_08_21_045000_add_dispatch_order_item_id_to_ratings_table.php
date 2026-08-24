<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ratings')) {
            return;
        }

        Schema::table('ratings', function (Blueprint $table) {
            if (! Schema::hasColumn('ratings', 'dispatch_order_item_id')) {
                $table->unsignedBigInteger('dispatch_order_item_id')->nullable()->after('order_id');
                $table->foreign('dispatch_order_item_id')
                    ->references('id')
                    ->on('dispatch_order_items')
                    ->nullOnDelete();
                $table->unique(['dispatch_order_item_id', 'user_id'], 'ratings_item_user_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ratings') || ! Schema::hasColumn('ratings', 'dispatch_order_item_id')) {
            return;
        }

        Schema::table('ratings', function (Blueprint $table) {
            $table->dropUnique('ratings_item_user_unique');
            $table->dropForeign(['dispatch_order_item_id']);
            $table->dropColumn('dispatch_order_item_id');
        });
    }
};
